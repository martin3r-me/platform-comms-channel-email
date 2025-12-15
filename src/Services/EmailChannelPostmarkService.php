<?php

namespace Platform\Comms\ChannelEmail\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Platform\Comms\ChannelEmail\Models\{
    CommsChannelEmailAccount,
    CommsChannelEmailThread as Thread,
    CommsChannelEmailOutboundMail as OutboundMail,
    CommsChannelEmailMailAttachment as MailAttachment
};
use Platform\Core\Models\User;
use Platform\Core\Models\Team;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkAttachment;

class EmailChannelPostmarkService
{
    protected PostmarkClient $client;
    protected array $cfg;

    public function __construct(Config $config)
    {
        $this->cfg = $config->get('channel-email');
        $this->client = new PostmarkClient($this->cfg['server_token']);
    }

    public function send(
        CommsChannelEmailAccount $account,
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        array $files = [],
        array $opt = [],
    ): string {
        // ---------------------------------------------------------
        // 1) Thread & Token
        // ---------------------------------------------------------
        $token = $opt['token'] ?? Str::ulid()->toBase32();
        $thread = Thread::firstOrCreate(
            [
                'token' => $token,
                'email_account_id' => $account->id,
            ],
            [
                'subject' => $subject,
            ]
        );

        // ---------------------------------------------------------
        // 1b) Kontext an Thread hängen (z. B. Helpdesk-Ticket)
        // ---------------------------------------------------------
        if (!empty($opt['context']['model']) && !empty($opt['context']['modelId'])) {
            $thread->contexts()->firstOrCreate([
                'context_type' => $opt['context']['model'],
                'context_id'   => $opt['context']['modelId'],
            ]);
        }

        // ---------------------------------------------------------
        // 2) Re: Prefix (nur bei Antworten)
        // ---------------------------------------------------------
        if (($opt['is_reply'] ?? false) && !preg_match('/^Re:/i', $subject)) {
            $subject = 'Re: ' . $subject;
        }

        // ---------------------------------------------------------
        // 3) Thread-Statistik (nur bei Folgeantworten)
        // ---------------------------------------------------------
        $mailCount = $thread->inboundMails()->count() + $thread->outboundMails()->count();
        $startDate = $thread->created_at ?? now();

        if ($mailCount > 0) {
            $diffInSeconds = $startDate->diffInSeconds(now());

            if ($diffInSeconds < 86400) {
                $durationLabel = 'heute gestartet';
            } elseif ($diffInSeconds < 172800) {
                $durationLabel = 'seit 1 Tag';
            } else {
                $days = floor($diffInSeconds / 86400);
                $durationLabel = "seit {$days} Tagen";
            }

            $htmlStats = <<<HTML
<hr>
<p style="font-size: 13px; color: #666;">
    <strong>Thread:</strong> #{$token}<br>
    <strong>Start:</strong> {$startDate->format('d.m.Y H:i')}<br>
    <strong>Dauer:</strong> {$durationLabel}<br>
    <strong>Nachrichten:</strong> {$mailCount}
</p>
HTML;

            $textStats = <<<TXT

---
Thread: #{$token}
Start: {$startDate->format('d.m.Y H:i')}
Dauer: {$durationLabel}
Nachrichten: {$mailCount}
TXT;
        } else {
            $htmlStats = '';
            $textStats = '';
        }

        // ---------------------------------------------------------
        // 4) Body inkl. Marker & Statistik
        // ---------------------------------------------------------
        $marker = "[conv:$token]";
        $htmlBody .= "\n<!-- conversation-token:$token --><span style=\"display:block;\">$marker</span>";
        $htmlBody .= $htmlStats;

        $textBody ??= strip_tags($htmlBody);
        $textBody .= "\n\n$marker" . $textStats;

        // ---------------------------------------------------------
        // 5) Attachments
        // ---------------------------------------------------------
        $pmAttachments = [];
        $storedAttachments = [];

        foreach ($files as $file) {
            $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
            if (!is_file($path) || filesize($path) === 0) {
                continue;
            }

            $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($path);
            $mime = $file instanceof UploadedFile ? $file->getClientMimeType() : mime_content_type($path);

            $pmAttachments[] = PostmarkAttachment::fromFile($path, $name, $mime);

            if ($file instanceof UploadedFile) {
                $storedPath = "threads/{$thread->id}/$name";
                Storage::disk('emails')->putFileAs("threads/{$thread->id}", $file, $name);
            } else {
                $storedPath = $path;
            }

            $storedAttachments[] = compact('name', 'mime', 'storedPath');
        }

        $pmAttachments = $pmAttachments ?: null;

        // ---------------------------------------------------------
        // 6) Header (nur bei Attachments)
        // ---------------------------------------------------------
        $headersArray = $pmAttachments
            ? [['Name' => 'X-Conversation-Token', 'Value' => $token]]
            : null;

        // ---------------------------------------------------------
        // 7) Versand via Postmark
        // ---------------------------------------------------------
        $from = $account->name
            ? "{$account->name} <{$account->address}>"
            : $account->address;

        $this->client->sendEmail(
            $from,
            $to,
            $subject,
            $htmlBody,
            $textBody,
            $opt['tag'] ?? $this->cfg['defaults']['tag'],
            $opt['track_opens'] ?? $this->cfg['defaults']['track_opens'],
            $opt['reply_to'] ?? null,
            $opt['cc'] ?? null,
            $opt['bcc'] ?? null,
            $pmAttachments,
            $headersArray,
            $opt['track_links'] ?? $this->cfg['defaults']['track_links'],
            null,
            $opt['stream'] ?? $this->cfg['message_stream'],
        );

        // ---------------------------------------------------------
        // 8) Persistieren der Outbound-Mail
        // ---------------------------------------------------------
        $mail = new OutboundMail([
            'thread_id'        => $thread->id,
            'email_account_id' => $account->id,
            'from'             => $from,
            'to'               => $to,
            'cc'               => $opt['cc'] ?? null,
            'bcc'              => $opt['bcc'] ?? null,
            'reply_to'         => $opt['reply_to'] ?? null,
            'subject'          => $subject,
            'html_body'        => $htmlBody,
            'text_body'        => $textBody,
            'sent_at'          => now(),
        ]);

        $mail->thread()->associate($thread);

        if (isset($opt['sender'])) {
            $actor = $opt['sender'];
            if ($actor instanceof User) {
                $mail->user()->associate($actor);
            } elseif ($actor instanceof Team) {
                $mail->team()->associate($actor);
            }
        }

        $mail->save();

        // ---------------------------------------------------------
        // 9) Attachments persistieren
        // ---------------------------------------------------------
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