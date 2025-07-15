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
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        array  $files = [],
        array  $opt   = [],
    ): string {
        /* -------------------------------------------------------------
         | 1) Thread + Token
         *-------------------------------------------------------------*/
        $token  = $opt['token'] ?? Str::ulid()->toBase32();
        $thread = Thread::firstOrCreate(
            ['token' => $token],
            ['subject' => $subject]
        );

        /* -------------------------------------------------------------
         | 2) Body-Marker
         *-------------------------------------------------------------*/
        $marker = "[conv:$token]";
        $htmlBody .= "\n<!-- conversation-token:$token -->";
        $htmlBody .= "\n<span style=\"display:none;\">$marker</span>";
        $textBody ??= strip_tags($htmlBody);
        $textBody .= "\n\n$marker";

        /* -------------------------------------------------------------
         | 3) Attachments vorbereiten
         *-------------------------------------------------------------*/
        $pmAttachments = [];
        $mailAttachmentsMeta = [];

        foreach ($files as $file) {
            $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
            $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($path);
            $mime = $file instanceof UploadedFile ? $file->getClientMimeType() : mime_content_type($path);
            $size = filesize($path);

            // a) PostmarkAttachment
            $pmAttachments[] = PostmarkAttachment::fromFile($path, $name, $mime);

            // b) Datei ins Storage kopieren (optional: wenn UploadedFile)
            if ($file instanceof UploadedFile) {
                $storedPath = "threads/{$thread->id}/{$name}";
                Storage::disk('emails')->putFileAs("threads/{$thread->id}", $file, $name);
            } else {
                $storedPath = $path; // bereits auf Disk
            }

            // c) Attachment-Meta
            $mailAttachmentsMeta[] = compact('name', 'mime', 'size', 'storedPath');
        }

        /* -------------------------------------------------------------
         | 4) Versand über Postmark
         *-------------------------------------------------------------*/

        // … Attachment-Schleife oben …
        
        $pmAttachments = empty($pmAttachments) ? null : $pmAttachments;
        $headersArray  = [['Name' => 'X-Conversation-Token', 'Value' => $token]];

        $this->client->sendEmail(
            $opt['from']        ?? $this->cfg['from'],                 // 1 from
            $to,                                                      // 2 to
            $subject,                                                 // 3 subject
            $htmlBody,                                                // 4 html
            $textBody,                                                // 5 text
            $opt['tag']         ?? $this->cfg['defaults']['tag'],      // 6 tag
            $opt['track_opens'] ?? $this->cfg['defaults']['track_opens'], // 7 trackOpens
            $opt['reply_to']    ?? null,                              // 8 reply-to
            $opt['cc']          ?? null,                              // 9 cc
            $opt['bcc']         ?? null,                              //10 bcc
            $pmAttachments,                                           //11 attachments
            $headersArray,                                            //12 headers
            $opt['track_links'] ?? $this->cfg['defaults']['track_links'], //13 trackLinks  (STRING)
            null,                                                     //14 metadata      (ARRAY/NULL)
            $opt['stream']      ?? $this->cfg['message_stream'],      //15 messageStream (STRING/NULL)
        );

        /* -------------------------------------------------------------
         | 5) OutboundMail speichern
         *-------------------------------------------------------------*/
        $mail = new OutboundMail([
            'thread_id' => $thread->id,
            'from'      => $opt['from']     ?? $this->cfg['from'],
            'to'        => $to,
            'cc'        => $opt['cc']      ?? null,
            'bcc'       => $opt['bcc']     ?? null,
            'reply_to'  => $opt['reply_to']?? null,
            'subject'   => $subject,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
            'sent_at'   => now(),
            'meta'      => ['attachments' => count($files)],
        ]);

        // FK-Zuordnungen (User / Team / polymorph)
        if (isset($opt['sender'])) {
            $actor = $opt['sender'];
            if ($actor instanceof \App\Models\User) {
                $mail->user()->associate($actor);
            } elseif ($actor instanceof \App\Models\Team) {
                $mail->team()->associate($actor);
            } elseif ($actor) {
                $mail->sender()->associate($actor);
            }
        }

        $mail->thread()->associate($thread);
        $mail->save();

        /* -------------------------------------------------------------
         | 6) Attachments persistieren
         *-------------------------------------------------------------*/
        foreach ($mailAttachmentsMeta as $meta) {
            $mail->attachments()->create([
                'filename' => $meta['name'],
                'mime'     => $meta['mime'],
                'size'     => $meta['size'],
                'disk'     => 'emails',
                'path'     => $meta['storedPath'],
                'inline'   => false,
            ]);
        }

        return $token;
    }
}