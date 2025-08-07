<?php

namespace Platform\Comms\ChannelEmail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modell: Platform\Comms\ChannelEmail\Models\CommsChannelEmailAccount
 *
 * Repräsentiert eine absendende Mail-Adresse (Postfach).
 * Kann einem Team und optional einem Nutzer zugeordnet sein.
 */
class CommsChannelEmailAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'email',
        'name',
        'team_id',
        'user_id',
        'sender_type',
        'sender_id',
        'meta',
        'is_default',
    ];

    protected $casts = [
        'meta'       => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Team-Zugehörigkeit
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    /**
     * Optionaler Benutzer (z. B. persönliche Inbox)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Polymorpher technischer Absender (z. B. Bot, SystemNutzer)
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function threads()
    {
        return $this->hasMany(CommsChannelEmailThread::class, 'email_account_id');
    }
}