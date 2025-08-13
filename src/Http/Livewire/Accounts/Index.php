<?php

namespace Platform\Comms\ChannelEmail\Http\Livewire\Accounts;

use Livewire\Component;
use Livewire\Attributes\Computed;

use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Platform\Comms\ChannelEmail\Models\{
    CommsChannelEmailAccount,
    CommsChannelEmailThread
};
use Platform\Comms\ChannelEmail\Services\EmailChannelPostmarkService;

class Index extends Component
{
    public int $account_id;
    public CommsChannelEmailAccount $account;

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
        return $this->account->threads()->latest()->get();
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





    public function mount(int $account_id, array $context = []): void
    {
        $this->account_id = $account_id;
        $this->context = $context;
        $this->account = CommsChannelEmailAccount::with('threads')->findOrFail($this->account_id);
    }

    public function startNewMessage(): void
    {
        $this->reset('activeThread', 'replyBody', 'activeMessageId', 'activeMessageDirection');
        $this->composeMode = true;

        $this->compose['to'] = collect($this->context['recipients'] ?? [])
            ->pluck('email')
            ->filter()
            ->implode(', ');

        if ($this->showContextDetails) {
            $this->compose['subject'] = $this->context['subject'] ?? '';
            $this->compose['body'] = $this->context['description'] ?? '';
        }
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
    }

    public function sendNewMessage(): void
    {
        $this->validate([
            'compose.to' => 'required|email',
            'compose.subject' => 'required|string',
            'compose.body' => 'required|string',
        ]);

        $bodyText = trim($this->compose['body']);
        $htmlBody = nl2br(e($bodyText));

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