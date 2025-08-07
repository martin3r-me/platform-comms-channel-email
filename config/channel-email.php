<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Postmark Server Token
    |--------------------------------------------------------------------------
    */
    'server_token' => env('POSTMARK_SERVER_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Standard-Absender
    |--------------------------------------------------------------------------
    */
    'from' => env('POSTMARK_FROM', 'noreply@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Nachrichtentyp / Stream
    |--------------------------------------------------------------------------
    */
    'message_stream' => env('POSTMARK_MESSAGE_STREAM', 'outbound'),

    /*
    |--------------------------------------------------------------------------
    | Inbound-Authentifizierung für Webhook-Zugriff
    |--------------------------------------------------------------------------
    */
    'inbound' => [
        'username' => env('POSTMARK_INBOUND_USER'),
        'password' => env('POSTMARK_INBOUND_PASS'),
        'signing_secret' => env('POSTMARK_INBOUND_SECRET'), // optional für Signature-Checks
    ],

    /*
    |--------------------------------------------------------------------------
    | Queueing für ausgehende E-Mails
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled'    => env('POSTMARK_QUEUE', false),
        'connection' => env('POSTMARK_QUEUE_CONNECTION'),
        'queue'      => env('POSTMARK_QUEUE_NAME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking-Optionen
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'track_opens' => env('POSTMARK_TRACK_OPENS', true),
        'track_links' => env('POSTMARK_TRACK_LINKS', 'None'),
        'tag'         => env('POSTMARK_DEFAULT_TAG'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbound-Speicherung
    |--------------------------------------------------------------------------
    */
    'models' => [
        'inbound_email' => env('POSTMARK_INBOUND_MODEL'), // z. B. App\Models\InboundEmail::class
    ],
];