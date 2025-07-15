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

        // 1) Token aus Header ODER Body
        $token = $request->header('X-Conversation-Token');
        if (!$token && isset($payload['TextBody'])) {
            preg_match('/\[conv:([A-Z0-9]{26})]/', $payload['TextBody'], $m);
            $token = $m[1] ?? null;
        }

        // 2) Thread holen oder anlegen
        $thread = Thread::firstOrCreate(
            ['token' => $token ?: str()->ulid()->toBase32()],
            ['subject' => $payload['Subject'] ?? null]
        );

        // 3) InboundMail speichern
        $mail = $thread->inboundMails()->create([
            'from'        => $payload['From'],
            'to'          => $payload['ToFull']     ?? $payload['To'],
            'cc'          => $payload['CcFull']     ?? null,
            'reply_to'    => $payload['ReplyTo']    ?? null,
            'subject'     => $payload['Subject'],
            'html_body'   => $payload['HtmlBody']   ?? null,
            'text_body'   => $payload['TextBody']   ?? null,
            'headers'     => $payload['Headers']    ?? null,
            'attachments' => null,                  // füllen wir unten
            'spam_score'  => $payload['SpamScore']  ?? null,
            'received_at' => now(),
        ]);

        // 4) Attachments sichern
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

        return response()->noContent();  // 204 → Postmark happy
    }
}