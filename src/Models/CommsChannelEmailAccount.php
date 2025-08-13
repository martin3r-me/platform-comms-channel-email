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
        'created_by_user_id',
        'user_id',
        'ownership_type',
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
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Benutzer, der das Konto erstellt hat
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    /**
     * Optionaler Benutzer (z. B. persönliche Inbox)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
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

    /**
     * Benutzer mit Zugriff auf dieses Konto (Many-to-Many)
     */
    public function sharedUsers()
    {
        return $this->belongsToMany(\Platform\Core\Models\User::class, 'comms_channel_email_account_user', 'account_id', 'user_id')
                    ->withTimestamps()
                    ->withPivot(['granted_at', 'revoked_at']);
    }

    /**
     * Prüft, ob ein Benutzer Zugriff auf dieses Konto hat
     */
    public function hasUserAccess(\Platform\Core\Models\User $user): bool
    {
        // Ersteller hat immer Zugriff
        if ($this->created_by_user_id === $user->id) {
            return true;
        }

        // Privater Besitzer hat Zugriff
        if ($this->ownership_type === 'user' && $this->user_id === $user->id) {
            return true;
        }

        // Team-Mitglieder haben Zugriff auf Team-Konten
        if ($this->ownership_type === 'team' && $this->team_id === $user->currentTeam?->id) {
            return true;
        }

        // Explizit geteilte Benutzer
        return $this->sharedUsers()->where('user_id', $user->id)->exists();
    }
}