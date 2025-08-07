<?php

namespace Platform\Comms\ChannelEmail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modell: Platform\Comms\ChannelEmail\Models\CommsChannelEmailOutboundMail
 *
 * Repräsentiert eine ausgehende E-Mail, versendet über Postmark.
 */
class CommsChannelEmailOutboundMail extends Model
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
        'bcc',
        'reply_to',
        'subject',
        'html_body',
        'text_body',
        'meta',
        'sent_at',
    ];

    /*------------------------------------------------------------------------
     |  Type-Casts
     *-----------------------------------------------------------------------*/
    protected $casts = [
        'meta'    => 'array',
        'sent_at' => 'datetime',
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

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }
}