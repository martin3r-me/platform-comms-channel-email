<?php

namespace Platform\Comms\ChannelEmail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommsChannelEmailThreadContext extends Model
{
    protected $fillable = [
        'comms_channel_email_thread_id',
        'context_type',
        'context_id',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CommsChannelEmailThread::class);
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }
}