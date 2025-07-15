<?php


namespace Martin3r\LaravelInboundOutboundMail\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Martin3r\LaravelInboundOutboundMail\Models\{
    Thread,
    OutboundMail,
    MailAttachment
};
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkAttachment;

/**
 * Versandservice für Postmark-E-Mails – inkl. Thread-, OutboundMail-
 * und Attachment-Persistenz.
 */
class PostmarkMailService
{
    private PostmarkClient $client;
    private array $cfg;

    public function __construct(Config $config)
    {
        $this->cfg    = $config->get('inbound-outbound-mail');
        $this->client = new PostmarkClient($this->cfg['server_token']);
    }

    /**
     * Schickt eine Mail, legt Thread + OutboundMail + Attachments an
     * und gibt den Thread-Token zurück.
     *
     * @param  string                        $to
     * @param  string                        $subject
     * @param  string                        $htmlBody
     * @param  string|null                   $textBody
     * @param  array<int,UploadedFile|string> $files  Pfade ODER UploadedFiles
     * @param  array{
     *     from?:string, cc?:string, bcc?:string, reply_to?:string,
     *     tag?:string, stream?:string,
     *     track_opens?:bool, track_links?:string,
     *     token?:string,
     *     sender?:\Illuminate\Database\Eloquent\Model|mixed
     * } $opt
     *
     * @return string  Thread-Token
     */
    /**
     * Versendet eine Mail, erzeugt Thread, OutboundMail & Attachments.
     * Gibt den Thread-Token zurück.
     */
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        array  $files     = [],
        array  $opt       = [],
    ): string {

        /* --------------------------------------------------------- */
        /* 1) Thread & Token                                         */
        /* --------------------------------------------------------- */
        $token  = $opt['token'] ?? Str::ulid()->toBase32();
        $thread = Thread::firstOrCreate(
            ['token' => $token],
            ['subject' => $subject]
        );

        /* --------------------------------------------------------- */
        /* 2) Body-Marker                                            */
        /* --------------------------------------------------------- */
        $marker   = "[conv:$token]";
        $htmlBody .= "\n<!-- conversation-token:$token --><span style=\"display:none;\">$marker</span>";
        $textBody ??= strip_tags($htmlBody);
        $textBody .= "\n\n$marker";

        /* --------------------------------------------------------- */
        /* 3) Attachments                                            */
        /* --------------------------------------------------------- */
        $pmAttachments      = [];
        $storedAttachments  = [];

        foreach ($files as $file) {
            $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
            if (!is_file($path) || filesize($path) === 0) {
                continue;                                       // 0-Byte überspringen
            }

            $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($path);
            $mime = $file instanceof UploadedFile ? $file->getClientMimeType() : mime_content_type($path);

            $pmAttachments[] = PostmarkAttachment::fromFile($path, $name, $mime);

            // im Storage sichern, falls es ein Upload war
            if ($file instanceof UploadedFile) {
                $storedPath = "threads/{$thread->id}/$name";
                Storage::disk('emails')->putFileAs("threads/{$thread->id}", $file, $name);
            } else {
                $storedPath = $path;
            }

            $storedAttachments[] = compact('name', 'mime', 'storedPath');
        }

        // Postmark erwartet: null = keine Attachments (leeres Array löst Fehler aus)
        $pmAttachments = $pmAttachments ?: null;

        /* --------------------------------------------------------- */
        /* 4) Header nur setzen, wenn auch Attachments existieren    */
        /* --------------------------------------------------------- */
        $headersArray = $pmAttachments
            ? [['Name' => 'X-Conversation-Token', 'Value' => $token]]
            : null;

        /* --------------------------------------------------------- */
        /* 5) Versand                                                */
        /* --------------------------------------------------------- */
        $this->client->sendEmail(
            $opt['from']        ?? $this->cfg['from'],                      // 1
            $to,                                                           // 2
            $subject,                                                      // 3
            $htmlBody,                                                     // 4
            $textBody,                                                     // 5
            $opt['tag']         ?? $this->cfg['defaults']['tag'],          // 6 Tag
            $opt['track_opens'] ?? $this->cfg['defaults']['track_opens'],  // 7 TrackOpens
            $opt['reply_to']    ?? null,                                   // 8 Reply-To
            $opt['cc']          ?? null,                                   // 9 CC
            $opt['bcc']         ?? null,                                   //10 BCC
            $pmAttachments,                                                //11 Attachments (null|array)
            $headersArray,                                                 //12 Headers (null|array)
            $opt['track_links'] ?? $this->cfg['defaults']['track_links'],  //13 TrackLinks (string)
            null,                                                          //14 Metadata   (null)
            $opt['stream']      ?? $this->cfg['message_stream'],           //15 MessageStream
        );

        /* --------------------------------------------------------- */
        /* 6) OutboundMail speichern                                 */
        /* --------------------------------------------------------- */
        $mail = new OutboundMail([
            'thread_id' => $thread->id,
            'from'      => $opt['from'] ?? $this->cfg['from'],
            'to'        => $to,
            'cc'        => $opt['cc']  ?? null,
            'bcc'       => $opt['bcc'] ?? null,
            'reply_to'  => $opt['reply_to'] ?? null,
            'subject'   => $subject,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
            'sent_at'   => now(),
        ]);
        $mail->thread()->associate($thread);

        // Absender verknüpfen (User, Team oder polymorph)
        if (isset($opt['sender'])) {
            $actor = $opt['sender'];
            if ($actor instanceof \App\Models\User) {
                $mail->user()->associate($actor);
            } elseif ($actor instanceof \App\Models\Team) {
                $mail->team()->associate($actor);
            } else {
                $mail->sender()->associate($actor);
            }
        }
        $mail->save();

        /* --------------------------------------------------------- */
        /* 7) Attachments persistieren                               */
        /* --------------------------------------------------------- */
        foreach ($storedAttachments as $a) {
            $mail->attachments()->create([
                'filename' => $a['name'],
                'mime'     => $a['mime'],
                'size'     => Storage::disk('emails')->size($a['storedPath']),
                'disk'     => 'emails',
                'path'     => $a['storedPath'],
                'inline'   => false,
            ]);
        }

        return $token;
    }
}