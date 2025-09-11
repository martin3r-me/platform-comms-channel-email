<?php

namespace Platform\Comms\ChannelEmail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modell: Platform\Comms\ChannelEmail\Models\CommsChannelEmailMailAttachment
 *
 * Anhänge zu einer Inbound- oder Outbound-Mail.
 * Polymorphe Beziehung: mail_type, mail_id
 */
class CommsChannelEmailMailAttachment extends Model
{
    use SoftDeletes;

    /**
     * Erzwingt den Tabellennamen, da der Klassenname sonst zu
     * "comms_channel_email_mail_attachments" pluralisiert würde.
     */
    protected $table = 'comms_channel_email_attachments';

    protected $fillable = [
        'filename',
        'mime',
        'size',
        'disk',
        'path',
        'cid',
        'inline',
    ];

    protected $casts = [
        'inline' => 'boolean',
        'size'   => 'integer',
    ];

    public function mail()
    {
        return $this->morphTo(); // → CommsChannelEmailInboundMail | CommsChannelEmailOutboundMail
    }
}