<?php

namespace Platform\Comms\ChannelEmail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Platform\Comms\ChannelEmail\Models\{
    CommsChannelEmailThread as Thread,
    CommsChannelEmailInboundMail as InboundMail,
    CommsChannelEmailAccount as EmailAccount
};


class InboundPostmarkController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->json()->all();

        // ------------------------------------------------------------
        // 1) E-Mail-Account validieren
        // ------------------------------------------------------------
        $emailAccount = $this->findEmailAccount($payload);
        
        if (!$emailAccount) {
            Log::warning('Rejected email for unknown account', [
                'to' => $payload['To'] ?? 'unknown',
                'from' => $payload['From'] ?? 'unknown',
                'subject' => $payload['Subject'] ?? 'unknown'
            ]);
            return response()->noContent(); // 204 → Postmark happy, aber ignoriert
        }

        // ------------------------------------------------------------
        // 2) Token aus Header oder Body extrahieren
        // ------------------------------------------------------------
        $token = $request->header('X-Conversation-Token');
        if (! $token && isset($payload['TextBody'])) {
            preg_match('/\[conv:([A-Z0-9]{26})]/', $payload['TextBody'], $m);
            $token = $m[1] ?? null;
        }

        // ------------------------------------------------------------
        // 3) Thread holen oder neu anlegen
        // ------------------------------------------------------------
        $thread = $emailAccount->threads()->firstOrCreate(
            ['token' => $token ?: str()->ulid()->toBase32()],
            ['subject' => $payload['Subject'] ?? null]
        );

        // ------------------------------------------------------------
        // 4) Hilfsfunktion für Adressfelder
        // ------------------------------------------------------------
        $addr = static function ($raw) {
            return match (true) {
                is_null($raw)   => null,
                is_string($raw) => $raw,
                default         => collect($raw)->pluck('Email')->implode(','),
            };
        };

        // ------------------------------------------------------------
        // 5) Mail speichern
        // ------------------------------------------------------------
        $mail = $thread->inboundMails()->create([
            'from'        => $payload['From'],
            'to'          => $addr($payload['ToFull'] ?? $payload['To']),
            'cc'          => $addr($payload['CcFull'] ?? null),
            'reply_to'    => $addr($payload['ReplyTo'] ?? null),
            'subject'     => $payload['Subject'],
            'html_body'   => $payload['HtmlBody'] ?? null,
            'text_body'   => $payload['TextBody'] ?? null,
            'headers'     => $payload['Headers'] ?? null,
            'attachments' => $payload['Attachments'] ?? null,
            'spam_score'  => $payload['SpamScore'] ?? null,
            'received_at' => now(),
        ]);

        // ------------------------------------------------------------
        // 6) Attachments persistieren
        // ------------------------------------------------------------
        foreach ($payload['Attachments'] ?? [] as $a) {
            $path = "threads/{$thread->id}/{$a['Name']}";
            Storage::disk('emails')->put($path, base64_decode($a['Content']));

            $mail->attachments()->create([
                'filename' => $a['Name'],
                'mime'     => $a['ContentType'],
                'size'     => $a['ContentLength'],
                'disk'     => 'emails',
                'path'     => $path,
                'cid'      => $a['ContentID'] ?? null,
                'inline'   => isset($a['ContentID']),
            ]);
        }

        Log::info('Email processed successfully', [
            'account' => $emailAccount->address,
            'thread_id' => $thread->id,
            'mail_id' => $mail->id
        ]);

        return response()->noContent(); // 204 → Postmark happy
    }

    /**
     * Findet den E-Mail-Account basierend auf Empfänger-Adresse
     */
    private function findEmailAccount(array $payload): ?EmailAccount
    {
        $recipients = $this->extractRecipients($payload);
        
        foreach ($recipients as $recipient) {
            $account = EmailAccount::where('address', $recipient)->first();
            if ($account) {
                return $account;
            }
        }
        
        return null;
    }

    /**
     * Extrahiert alle Empfänger-Adressen aus der E-Mail
     */
    private function extractRecipients(array $payload): array
    {
        $recipients = [];
        
        // To-Feld
        if (isset($payload['To'])) {
            if (is_string($payload['To'])) {
                $recipients[] = $payload['To'];
            } elseif (is_array($payload['To'])) {
                $recipients = array_merge($recipients, collect($payload['To'])->pluck('Email')->toArray());
            }
        }
        
        // ToFull-Feld (detaillierter)
        if (isset($payload['ToFull'])) {
            $recipients = array_merge($recipients, collect($payload['ToFull'])->pluck('Email')->toArray());
        }
        
        return array_unique(array_filter($recipients));
    }
}