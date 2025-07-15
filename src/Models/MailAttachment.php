<?php

namespace Martin3r\LaravelInboundOutboundMail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'filename','mime','size','disk','path','cid','inline',
    ];

    protected $casts = [
        'inline' => 'boolean',
        'size'   => 'integer',
    ];

    public function mail()   // belongs to InboundMail *oder* OutboundMail
    {
        return $this->morphTo();
    }
}
