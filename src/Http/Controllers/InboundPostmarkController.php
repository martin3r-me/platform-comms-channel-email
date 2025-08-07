<?php

namespace Platform\Comms\ChannelEmail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Platform\Comms\ChannelEmail\Models\{
    CommsChannelEmailThread as Thread,
    CommsChannelEmailInboundMail as InboundMail
};
use Platform\Crm\Services\ContactLinkService;

class InboundPostmarkController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->json()->all();

        // ------------------------------------------------------------
        // 1) Token aus Header oder Body extrahieren
        // ------------------------------------------------------------
        $token = $request->header('X-Conversation-Token');
        if (! $token && isset($payload['TextBody'])) {
            preg_match('/\[conv:([A-Z0-9]{26})]/', $payload['TextBody'], $m);
            $token = $m[1] ?? null;
        }

        // ------------------------------------------------------------
        // 2) Thread holen oder neu anlegen
        // ------------------------------------------------------------
        $thread = Thread::firstOrCreate(
            ['token' => $token ?: str()->ulid()->toBase32()],
            ['subject' => $payload['Subject'] ?? null]
        );

        // ------------------------------------------------------------
        // 3) Hilfsfunktion für Adressfelder
        // ------------------------------------------------------------
        $addr = static function ($raw) {
            return match (true) {
                is_null($raw)   => null,
                is_string($raw) => $raw,
                default         => collect($raw)->pluck('Email')->implode(','),
            };
        };

        // ------------------------------------------------------------
        // 4) Mail speichern
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
        // 5) Attachments persistieren
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

        // ------------------------------------------------------------
        // 6) Automatisches Contact-Linking
        // ------------------------------------------------------------
        try {
            $contactLinkService = app(ContactLinkService::class);
            $contactLinkService->autoLinkContacts($thread);
        } catch (\Exception $e) {
            // Log error but don't fail the webhook
            \Log::error('Contact linking failed for thread ' . $thread->id, [
                'error' => $e->getMessage(),
                'thread_id' => $thread->id,
            ]);
        }

        return response()->noContent(); // 204 → Postmark happy
    }
}