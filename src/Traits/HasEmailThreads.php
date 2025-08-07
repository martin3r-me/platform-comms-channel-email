<?php

namespace Platform\Comms\ChannelEmail\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Platform\Comms\ChannelEmail\Models\CommsChannelEmailThreadContext;

trait HasEmailThreads
{
    public function emailThreads(): MorphMany
    {
        return $this->morphMany(CommsChannelEmailThreadContext::class, 'context');
    }

    public function hasEmailThreads(): bool
    {
        return $this->emailThreads()->exists();
    }

    public function addEmailThread($thread): void
    {
        $this->emailThreads()->create([
            'comms_channel_email_thread_id' => $thread instanceof \Platform\Comms\ChannelEmail\Models\CommsChannelEmailThread
                ? $thread->id
                : $thread,
        ]);
    }
}