<?php

namespace Platform\Comms\ChannelEmail\Http\Livewire\Accounts;

use Livewire\Component;
use Livewire\Attributes\Computed;

use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Platform\Comms\Services\CommsActivityService;
use Platform\Comms\ChannelEmail\Models\{
    CommsChannelEmailAccount,
    CommsChannelEmailThread
};
use Platform\Comms\ChannelEmail\Services\EmailChannelPostmarkService;

class Index extends Component
{
    public int $account_id;
    public CommsChannelEmailAccount $account;
    public string $ui_mode = 'comms'; // comms|admin

    protected $rules = [
        'account.ownership_type' => 'required|in:team,user',
        'account.user_id' => 'nullable|exists:users,id',
    ];

    public bool $composeMode = false;
    public ?CommsChannelEmailThread $activeThread = null;
    public ?int $activeMessageId = null;
    public ?string $activeMessageDirection = null;

    public array $compose = [
        'to' => '',
        'subject' => '',
        'body' => '',
    ];

    public string $replyBody = '';
    public array $context = [];
    public bool $showContextDetails = false;
    public string $activeTab = 'messages';
    
    // Settings-Properties
    public string $editName = '';
    public string $userSearch = '';
    public bool $showUserModal = false;



    
    #[Computed]
    public function threads()
    {
        // Nur Threads im aktuellen Kontext laden, wenn Kontext gesetzt ist
        $query = $this->account->threads()->latest();

        if (!empty($this->context['model']) && !empty($this->context['modelId'])) {
            $query->whereHas('contexts', function ($q) {
                $q->where('context_type', $this->context['model'])
                  ->where('context_id', $this->context['modelId']);
            });
        }

        return $query->get();
    }

    #[Computed]
    public function sharedUsers()
    {
        return $this->account->sharedUsers()->with('currentTeam')->get();
    }

    #[Computed]
    public function availableUsers()
    {
        $currentUser = auth()->user();
        $team = $currentUser->currentTeam;
        
        if (!$team) {
            return collect();
        }

        return $team->users()
            ->where('users.id', '!=', $currentUser->id)
            ->whereNotIn('users.id', $this->account->sharedUsers->pluck('id'))
            ->get();
    }





    public function mount(int $account_id, array $context = [], string $ui_mode = 'comms'): void
    {
        $this->account_id = $account_id;
        $this->context = $context;
        $this->ui_mode = $ui_mode ?: 'comms';
        $this->account = CommsChannelEmailAccount::with('threads')->findOrFail($this->account_id);

        // UX:
        // - Im Kontext (Ticket/Task) beim Öffnen direkt den passenden Thread öffnen (neuester zuerst)
        // - Ohne Kontext NICHT auto-selecten (sonst wird es ein Mail-Client)
        $this->activeThread = null;
        $this->composeMode = false;
        $this->replyBody = '';

        if (
            $this->ui_mode === 'comms'
            && !empty($this->context['model'])
            && !empty($this->context['modelId'])
        ) {
            $this->activeThread = $this->account->threads()
                ->whereHas('contexts', function ($q) {
                    $q->where('context_type', $this->context['model'])
                      ->where('context_id', $this->context['modelId']);
                })
                ->latest()
                ->first();

            // Wenn wir automatisch einen Thread öffnen, gilt der Kontext/Channel als "gesehen"
            if ($this->activeThread && class_exists(CommsActivityService::class) && CommsActivityService::enabled()) {
                $userId = auth()->id();
                if ($userId) {
                    CommsActivityService::markSeen(
                        userId: (int) $userId,
                        channelId: 'email:' . $this->account_id,
                        contextType: (string) $this->context['model'],
                        contextId: (int) $this->context['modelId'],
                        teamId: auth()->user()?->currentTeam?->id,
                    );
                }
            }
        }
    }

    public function backToThreadList(): void
    {
        $this->reset('activeThread', 'replyBody', 'activeMessageId', 'activeMessageDirection');
        $this->composeMode = false;
    }

    public function startNewMessage(): void
    {
        $this->reset('activeThread', 'replyBody', 'activeMessageId', 'activeMessageDirection');
        $this->reset('compose');
        $this->composeMode = true;

        // Optional: Empfänger aus Kontext (falls bereits als {email: ...} geliefert)
        $this->compose['to'] = collect($this->context['recipients'] ?? [])
            ->pluck('email')
            ->filter()
            ->implode(', ');

        // Für neue Threads ist es hilfreich, Subject/Body aus dem Kontext vorzubelegen
        $this->compose['subject'] = $this->context['subject'] ?? '';
        $this->compose['body'] = $this->context['description'] ?? '';
    }

    public function updatedShowContextDetails(bool $value): void
    {
        if ($value) {
            if (empty($this->compose['subject']) && !empty($this->context['subject'])) {
                $this->compose['subject'] = $this->context['subject'];
            }

            if (empty($this->compose['body']) && !empty($this->context['description'])) {
                $this->compose['body'] = $this->context['description'];
            }
        }
    }

    public function cancelCompose(): void
    {
        $this->reset('compose', 'composeMode');
    }

    public function selectThread(int $threadId, int $messageId = null, string $direction = null): void
    {
        $this->activeThread = CommsChannelEmailThread::findOrFail($threadId);
        $this->activeMessageId = $messageId;
        $this->activeMessageDirection = $direction;
        $this->composeMode = false;
        $this->replyBody = '';

        // Unread für diesen Kontext/Channel als gesehen markieren
        if (!empty($this->context['model']) && !empty($this->context['modelId'])
            && class_exists(CommsActivityService::class) && CommsActivityService::enabled()
        ) {
            $userId = auth()->id();
            if ($userId) {
                CommsActivityService::markSeen(
                    userId: (int) $userId,
                    channelId: 'email:' . $this->account_id,
                    contextType: (string) $this->context['model'],
                    contextId: (int) $this->context['modelId'],
                    teamId: auth()->user()?->currentTeam?->id,
                );
            }
        }
    }

    public function sendNewMessage(): void
    {
        $this->validate([
            'compose.to' => 'required|string',
            'compose.subject' => 'required|string',
            'compose.body' => 'required|string',
        ]);

        // Mehrere Empfänger erlauben (comma/semicolon separated)
        $toRaw = (string) ($this->compose['to'] ?? '');
        $emails = collect(preg_split('/[;,]+/', $toRaw))
            ->map(fn ($e) => trim((string) $e))
            ->filter();

        if ($emails->isEmpty() || $emails->contains(fn ($e) => !filter_var($e, FILTER_VALIDATE_EMAIL))) {
            $this->addError('compose.to', 'Bitte eine oder mehrere gültige E-Mail-Adressen angeben (kommagetrennt).');
            return;
        }

        // Normalisiere Empfänger
        $this->compose['to'] = $emails->implode(', ');

        $bodyText = trim($this->compose['body']);
        $htmlBody = nl2br(e($bodyText));
        $history = $this->buildHistorySnippet(null); // keine Historie für neuen Thread
        $htmlBody .= $history['html'];
        $bodyText .= $history['text'];

        // Kontext anhängen, falls sichtbar
        if ($this->showContextDetails && !empty($this->context)) {
            $htmlBody .= '<hr>' . $this->getContextBlock()->toHtml();
        }

        app(EmailChannelPostmarkService::class)->send(
            account: $this->account,
            to: $this->compose['to'],
            subject: $this->compose['subject'],
            htmlBody: $htmlBody,
            textBody: $bodyText,
            opt: [
                'sender' => auth()->user(),
                'token' => Str::ulid()->toBase32(),
                'meta'  => $this->context['meta'] ?? [],
                'context' => $this->context ?? null,
            ]
        );

        $this->reset('compose', 'composeMode');
        $this->account->refresh();
    }

    public function sendReply(): void
    {
        if (!$this->activeThread) return;

        $last = $this->activeThread->timeline()->last();
        $to = $last->direction === 'inbound' ? $last->from : $last->to;
        $subject = $last->subject ? 'Re: ' . $last->subject : 'Antwort';

        $htmlBody = nl2br(e($this->replyBody));
        $textBody = trim($this->replyBody);

        $history = $this->buildHistorySnippet($this->activeThread, 5);
        $htmlBody .= $history['html'];
        $textBody .= $history['text'];

        app(EmailChannelPostmarkService::class)->send(
            account: $this->account,
            to: $to,
            subject: $subject,
            htmlBody: $htmlBody,
            textBody: $textBody,
            opt: [
                'sender' => auth()->user(),
                'token' => $this->activeThread->token,
                'is_reply' => true,
                'context' => $this->context ?? null,
            ]
        );

        $this->reset('replyBody');
        $this->account->refresh();
        $this->activeThread = $this->account->threads()->find($this->activeThread->id);
    }

    public function getContextBlock(): HtmlString
    {
        $html = '<div style="font-size: 12px; color: #666;">';
        $html .= '<strong>Kontext:</strong><br>';
        $html .= '<ul style="margin: 0; padding-left: 1em;">';

        if (!empty($this->context['model'])) {
            $html .= '<li><strong>Typ:</strong> ' . e(class_basename($this->context['model'])) . '</li>';
        }

        if (!empty($this->context['modelId'])) {
            $html .= '<li><strong>ID:</strong> ' . e($this->context['modelId']) . '</li>';
        }

        if (!empty($this->context['subject'])) {
            $html .= '<li><strong>Betreff:</strong> ' . e($this->context['subject']) . '</li>';
        }

        if (!empty($this->context['description'])) {
            $html .= '<li><strong>Beschreibung:</strong> ' . nl2br(e($this->context['description'])) . '</li>';
        }

        $html .= '</ul>';

        if (!empty($this->context['url'])) {
            $html .= '<div><strong>Link:</strong> <a href="' . e($this->context['url']) . '">' . e($this->context['url']) . '</a></div>';
        }

        if (!empty($this->context['meta']) && is_array($this->context['meta'])) {
            $html .= '<div><strong>Metadaten:</strong><ul style="margin: 0; padding-left: 1em;">';
            foreach ($this->context['meta'] as $key => $value) {
                if (!is_null($value)) {
                    $html .= '<li>' . e($key) . ': ' . e($value) . '</li>';
                }
            }
            $html .= '</ul></div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }

    /**
     * Baut einen History-Snippet (letzte N Nachrichten) für den Mailbody.
     */
    protected function buildHistorySnippet(?CommsChannelEmailThread $thread, int $limit = 3): array
    {
        if (!$thread) {
            return ['html' => '', 'text' => ''];
        }

        $messages = $thread->timeline()
            ->sortByDesc('occurred_at')
            ->take($limit)
            ->sortBy('occurred_at');

        if ($messages->isEmpty()) {
            return ['html' => '', 'text' => ''];
        }

        $html = '<hr><div style="font-size:12px;color:#666;"><strong>Verlauf (letzte ' . $messages->count() . '):</strong><ul style="margin:0;padding-left:1em;">';
        $text = "\n\n---\nVerlauf (letzte {$messages->count()}):\n";

        foreach ($messages as $msg) {
            $dir = $msg->direction === 'inbound' ? 'Von' : 'An';
            $when = \Carbon\Carbon::parse($msg->occurred_at)->format('d.m.Y H:i');
            $subject = $msg->subject ?? '';
            $html .= '<li><strong>' . e($dir) . ':</strong> ' . e($msg->from ?? $msg->to ?? '') . ' — ' . e($when) . ' — ' . e($subject) . '</li>';
            $text .= "{$dir}: " . ($msg->from ?? $msg->to ?? '') . " — {$when} — {$subject}\n";
        }

        $html .= '</ul></div>';

        return ['html' => $html, 'text' => $text];
    }

    // Settings-Methoden
    public function startEditName(): void
    {
        $this->editName = $this->account->name ?? '';
    }

    public function saveName(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
        ]);

        $this->account->update(['name' => $this->editName]);
        $this->editName = '';
    }

    public function cancelEditName(): void
    {
        $this->editName = '';
    }

    public function updatedAccountOwnershipType(): void
    {
        // Wenn zu Team gewechselt wird, user_id auf null setzen
        if ($this->account->ownership_type === 'team') {
            $this->account->user_id = null;
        }
        
        // Wenn zu User gewechselt wird, user_id auf aktuellen User setzen
        if ($this->account->ownership_type === 'user') {
            $this->account->user_id = auth()->user()->id;
        }
        
        $this->account->save();
        
        // Event dispatchen, dass sich der Account geändert hat
        $this->dispatch('comms-account-updated', accountId: $this->account->id);
    }

    public function updatedAccountUserId(): void
    {
        $this->account->save();
        
        // Event dispatchen, dass sich der Account geändert hat
        $this->dispatch('comms-account-updated', accountId: $this->account->id);
    }

    public function addUser(int $userId): void
    {
        $user = \Platform\Core\Models\User::findOrFail($userId);
        
        $this->account->sharedUsers()->attach($userId, [
            'granted_at' => now(),
        ]);

        $this->account->refresh();
    }

    public function removeUser(int $userId): void
    {
        $this->account->sharedUsers()->updateExistingPivot($userId, [
            'revoked_at' => now(),
        ]);

        $this->account->refresh();
    }



    public function render()
    {
        return view('comms-channel-email::livewire.accounts.index', [
            'contextBlockHtml' => $this->showContextDetails && !empty($this->context)
                ? $this->getContextBlock()->toHtml()
                : null,
        ]);
    }
}