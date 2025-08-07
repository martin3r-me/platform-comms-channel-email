<?php

namespace Platform\Comms\ChannelEmail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modell: Platform\Comms\ChannelEmail\Models\CommsChannelEmailInboundMail
 *
 * Repräsentiert eine eingehende E-Mail, verarbeitet über Postmark (Inbound Webhook).
 */
class CommsChannelEmailInboundMail extends Model
{
    use SoftDeletes;

    /*------------------------------------------------------------------------
     |  Mass-Assignment
     *-----------------------------------------------------------------------*/
    protected $fillable = [
        'thread_id',
        'user_id',
        'team_id',
        'sender_type',
        'sender_id',
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
        return $this->morphMany(CommsChannelEmailMailAttachment::class, 'mail');
    }

    public function thread()
    {
        return $this->belongsTo(CommsChannelEmailThread::class, 'thread_id');
    }

    public function sender()
    {
        return $this->morphTo();
    }
}