<?php


namespace Martin3r\LaravelInboundOutboundMail\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * E-Mail-Thread – fasst Outbound- und Inbound-Mails zusammen.
 *
 * Spalten: id (int), token (ULID/UUID string), subject, timestamps, deleted_at
 */
class Thread extends Model
{
    use SoftDeletes;

    /** @var array<int,string> */
    protected $fillable = ['token', 'subject'];

    /* -----------------------------------------------------------------
     |  Beziehungen
     |-----------------------------------------------------------------*/

    public function inboundMails()
    {
        return $this->hasMany(InboundMail::class);
    }

    public function outboundMails()
    {
        return $this->hasMany(OutboundMail::class);
    }

    /* -----------------------------------------------------------------
     |  Timeline — alle Mails chronologisch (Inbound + Outbound)
     |-----------------------------------------------------------------*/

    /**
     * Gibt eine chronologisch sortierte Collection aller Mails im Thread zurück.
     *
     * @return \Illuminate\Support\Collection<array-key, object>
     */
    public function timeline(): Collection
    {
        // ► Outbound-Mails
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

        // ► Inbound-Mails
        $in  = $this->inboundMails()
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

        // ► UNION + Sortierung
        return $out
            ->unionAll($in)
            ->orderBy('occurred_at')
            ->get();
    }
}
