<?php

namespace Platform\Comms\ChannelEmail\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Platform\Crm\Contracts\ContactLinkableInterface;

/**
 * Modell: Platform\Comms\ChannelEmail\Models\CommsChannelEmailThread
 *
 * Repräsentiert einen E-Mail-Thread – aggregiert Inbound- und Outbound-Mails.
 */
class CommsChannelEmailThread extends Model implements ContactLinkableInterface
{
    use SoftDeletes;

    protected $fillable = ['token', 'subject', 'email_account_id'];

    /*------------------------------------------------------------------------
     | Beziehungen
     *-----------------------------------------------------------------------*/
    public function account()
    {
        return $this->belongsTo(CommsChannelEmailAccount::class, 'email_account_id');
    }

    public function inboundMails()
    {
        return $this->hasMany(CommsChannelEmailInboundMail::class, 'thread_id');
    }

    public function outboundMails()
    {
        return $this->hasMany(CommsChannelEmailOutboundMail::class, 'thread_id');
    }

    public function contexts()
    {
        return $this->hasMany(CommsChannelEmailThreadContext::class);
    }

    /*------------------------------------------------------------------------
     | ContactLinkableInterface Implementation
     *-----------------------------------------------------------------------*/
    public function getContactLinkableId(): int
    {
        return $this->id;
    }

    public function getContactLinkableType(): string
    {
        return self::class;
    }

    public function getEmailAddresses(): array
    {
        $emailAddresses = collect();
        
        // Aus Inbound-Mails
        foreach ($this->inboundMails as $mail) {
            $emailAddresses->push($mail->from);
            $emailAddresses->push(...explode(',', $mail->to));
        }
        
        // Aus Outbound-Mails
        foreach ($this->outboundMails as $mail) {
            $emailAddresses->push($mail->from);
            $emailAddresses->push(...explode(',', $mail->to));
        }
        
        // Bereinige und normalisiere E-Mail-Adressen
        return $emailAddresses
            ->map(fn($email) => trim($email))
            ->filter(fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->toArray();
    }

    public function getTeamId(): int
    {
        return auth()->user()->current_team_id;
    }

    /*------------------------------------------------------------------------
     | Chronologische Timeline (Inbound + Outbound)
     *-----------------------------------------------------------------------*/

    /**
     * Gibt eine chronologisch sortierte Collection aller Mails im Thread zurück.
     *
     * @return \Illuminate\Support\Collection<array-key, object>
     */
    public function timeline(): Collection
    {
        $out = $this->outboundMails()
            ->select([
                'id',
                DB::raw("'outbound' as direction"),
                'from',
                'to',
                'subject',
                'html_body',
                'text_body',
                DB::raw('sent_at as occurred_at'),
            ]);

        $in = $this->inboundMails()
            ->select([
                'id',
                DB::raw("'inbound' as direction"),
                'from',
                'to',
                'subject',
                'html_body',
                'text_body',
                DB::raw('received_at as occurred_at'),
            ]);

        return $out
            ->unionAll($in)
            ->orderByDesc('occurred_at')
            ->get();
    }
}