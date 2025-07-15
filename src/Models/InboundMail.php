<?php


namespace Martin3r\LaravelInboundOutboundMail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Repräsentiert eine eingehende E-Mail, die Postmark per Webhook geliefert hat.
 *
 * Spalten (relevant):
 *  - id                INT          PK
 *  - thread_id         INT          FK → threads.id
 *  - postmark_id       VARCHAR      (MessageID aus Postmark, optional)
 *  - from, to, cc      TEXT/STRING
 *  - reply_to          STRING (nullable)
 *  - subject           STRING
 *  - html_body         LONGTEXT (nullable)
 *  - text_body         LONGTEXT (nullable)
 *  - headers           JSON (nullable)
 *  - attachments       JSON (nullable)
 *  - spam_score        DECIMAL 5,2  (nullable)
 *  - received_at       TIMESTAMP (nullable)
 *  - created_at / updated_at / deleted_at
 */
class InboundMail extends Model
{
    use SoftDeletes;

    /*------------------------------------------------------------------------
     |  Mass-Assignment
     *-----------------------------------------------------------------------*/
    protected $fillable = [
        'thread_id',
        'postmark_id',
        'from',
        'to',
        'cc',
        'reply_to',
        'subject',
        'html_body',
        'text_body',
        'headers',
        'attachments',
        'spam_score',
        'received_at',
    ];

    /*------------------------------------------------------------------------
     |  Type-Casts
     *-----------------------------------------------------------------------*/
    protected $casts = [
        'headers'      => 'array',
        'attachments'  => 'array',
        'received_at'  => 'datetime',
        'spam_score'   => 'float',
    ];

    /*------------------------------------------------------------------------
     |  Beziehungen
     *-----------------------------------------------------------------------*/
    public function attachments()
    {
        return $this->morphMany(MailAttachment::class, 'mail');
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }
}