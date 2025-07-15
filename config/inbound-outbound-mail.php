<?php
// config/postmark_mailer.php

return [

    /*
    |--------------------------------------------------------------------------
    | Postmark Server Token
    |--------------------------------------------------------------------------
    | API-Token aus deinem Postmark-Server („Server API Token“).
    | Zieh ihn immer aus der Umgebung – nie committen!
    */
    'server_token' => env('POSTMARK_SERVER_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Default "From"-Adresse
    |--------------------------------------------------------------------------
    | Absender, der bei jedem Versand vorbelegt wird.
    | Muss als Sender Signature oder Inbound-Adresse in Postmark existieren.
    */
    'from' => env('POSTMARK_FROM', 'noreply@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Message Stream
    |--------------------------------------------------------------------------
    | Für die meisten Accounts genügt "outbound".
    | Wenn du mehrere Streams nutzt (z. B. "broadcast", "transactional"),
    | trägst du hier den Standard ein und kannst ihn pro Mail überschreiben.
    */
    'message_stream' => env('POSTMARK_MESSAGE_STREAM', 'outbound'),

    /*
    |--------------------------------------------------------------------------
    | Inbound Webhook Secret
    |--------------------------------------------------------------------------
    | Gemeinsamer geheimer Schlüssel zur Signatur-Verifizierung
    | („Webhook Signing Secret“ im Postmark-UI).
    */
    'inbound_secret' => env('POSTMARK_INBOUND_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Queue-Einstellungen
    |--------------------------------------------------------------------------
    | Aktivierst du Queueing, wird das Mail-Senden als Job dispatched,
    | statt die HTTP-Request direkt im Request-Thread auszuführen.
    */
    'queue' => [
        'enabled'    => env('POSTMARK_QUEUE', false),
        'connection' => env('POSTMARK_QUEUE_CONNECTION'), // null → default
        'queue'      => env('POSTMARK_QUEUE_NAME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking-Defaults
    |--------------------------------------------------------------------------
    | Globale Vorgaben für Open/Link-Tracking und Tags.
    | Kannst du pro Aufruf individuell überschreiben.
    */
    'defaults' => [
        'track_opens' => env('POSTMARK_TRACK_OPENS', true),
        'track_links' => env('POSTMARK_TRACK_LINKS', 'None'),  // HtmlAndText, HtmlOnly, TextOnly, None
        'tag'         => env('POSTMARK_DEFAULT_TAG'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbound-Persistenz
    |--------------------------------------------------------------------------
    | Wenn du eingehende Mails speichern willst, setze hier dein Model ein.
    | Null ⇒ keine Persistenz, reines Event-Handling.
    */
    'models' => [
        'inbound_email' => env('POSTMARK_INBOUND_MODEL'), // z. B. App\Models\InboundEmail::class
    ],

];