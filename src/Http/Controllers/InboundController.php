<?php

namespace Martin3r\LaravelInboundOutboundMail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Martin3r\LaravelInboundOutboundMail\Models\{Thread, InboundMail};

class InboundController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->json()->all();

        /* ------------------------------------------------------------
         | 1) Token aus Header ODER Body extrahieren
         *----------------------------------------------------------- */
        $token = $request->header('X-Conversation-Token');
        if (! $token && isset($payload['TextBody'])) {
            preg_match('/\[conv:([A-Z0-9]{26})]/', $payload['TextBody'], $m);
            $token = $m[1] ?? null;
        }

        /* ------------------------------------------------------------
         | 2) Thread holen oder anlegen
         *----------------------------------------------------------- */
        $thread = Thread::firstOrCreate(
            ['token' => $token ?: str()->ulid()->toBase32()],
            ['subject' => $payload['Subject'] ?? null]
        );

        /* ------------------------------------------------------------
         | 3) Hilfs-Closure – Adressfelder sauber in String wandeln
         *----------------------------------------------------------- */
        $addr = static function ($raw) {
            return match (true) {
                is_null($raw)   => null,
                is_string($raw) => $raw,
                default         => collect($raw)->pluck('Email')->implode(','),
            };
        };

        /* ------------------------------------------------------------
         | 4) InboundMail anlegen
         *----------------------------------------------------------- */
        $mail = $thread->inboundMails()->create([
            'from'        => $payload['From'],
            'to'          => $addr($payload['ToFull'] ?? $payload['To']),
            'cc'          => $addr($payload['CcFull'] ?? null),
            'reply_to'    => $addr($payload['ReplyTo'] ?? null),
            'subject'     => $payload['Subject'],
            'html_body'   => $payload['HtmlBody'] ?? null,
            'text_body'   => $payload['TextBody'] ?? null,
            'headers'     => $payload['Headers'] ?? null,
            'spam_score'  => $payload['SpamScore'] ?? null,
            'received_at' => now(),
        ]);

        /* ------------------------------------------------------------
         | 5) Attachments sichern
         *----------------------------------------------------------- */
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

        return response()->noContent();          // 204 → Postmark happy
    }
}