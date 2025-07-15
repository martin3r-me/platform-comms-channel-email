<?php
namespace Martin3r\LaravelInboundOutboundMail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Repräsentiert eine ausgehende E-Mail, die über Postmark versendet wurde.
 *
 * Kerndaten:
 *  - id                INT           PK
 *  - thread_id         INT           FK → threads.id
 *  - user_id           INT?          FK → users.id      (nullable)
 *  - team_id           INT?          FK → teams.id      (nullable)
 *  - sender_type/id    polymorpher Fallback            (nullable)
 *  - postmark_id       VARCHAR?      MessageID aus Postmark
 *  - from, to, cc, bcc TEXT/STRING   (bcc nur outbound)
 *  - reply_to          STRING?       (nullable)
 *  - subject           STRING
 *  - html_body         LONGTEXT
 *  - text_body         LONGTEXT?     (nullable)
 *  - meta              JSON?         z. B. Tracking-Infos
 *  - sent_at           TIMESTAMP?    (nullable)
 *  - created_at / updated_at / deleted_at
 */
class OutboundMail extends Model
{
    use SoftDeletes;

    /*------------------------------------------------------------------------
     |  Mass-Assignment
     *-----------------------------------------------------------------------*/
    protected $fillable = [
        'thread_id',
        'user_id',
        'team_id',
        'sender_id',
        'sender_type',
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
        return $this->morphMany(MailAttachment::class, 'mail');
    }
    
    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Absender-User – nur, wenn im Projekt ein User-Model existiert.
     *
     * @return BelongsTo|null
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }


    /**
     * Absender-Team – nur, wenn ein Team-Model existiert.
     *
     * @return BelongsTo|null
     */
    public function user()
    {
        return $this->belongsTo(App\Models\Team::class);
    }


    /** Polymorpher Fallback-Absender (z. B. Bot, SystemUser, …) */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }
}