<?php

namespace Platform\Comms\ChannelEmail\Http\Livewire\Accounts;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Platform\Comms\ChannelEmail\Models\{
    CommsChannelEmailAccount,
    CommsChannelEmailThread
};
use Platform\Comms\ChannelEmail\Services\EmailChannelPostmarkService;
use Platform\Crm\Services\ContactLinkService;

class Index extends Component
{
    public int $account_id;
    public CommsChannelEmailAccount $account;

    public bool $composeMode = false;
    public ?CommsChannelEmailThread $activeThread = null;
    public ?int $activeMessageId = null;
    public ?string $activeMessageDirection = null;

    public array $compose = [
        'to' => '',
        'subject' => '',
        'body' => '',
    ];

    // Contact-Linking Properties
    public $selectedContactId = null;
    public $contactSearch = '';
    public $modalShow = false;
    public $selectedEmailAddress = '';
    public $showEmailSelection = false;

    public string $replyBody = '';
    public array $context = [];
    public bool $showContextDetails = false;

    public function mount(int $account_id, array $context = []): void
    {
        $this->account_id = $account_id;
        $this->context = $context;
        $this->account = CommsChannelEmailAccount::with('threads')->findOrFail($this->account_id);
    }

    public function startNewMessage(): void
    {
        $this->reset('activeThread', 'replyBody', 'activeMessageId', 'activeMessageDirection', 'selectedContactId');
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
        $this->reset('compose', 'composeMode', 'selectedContactId');
    }

    public function selectThread(int $threadId, int $messageId = null, string $direction = null): void
    {
        $this->activeThread = CommsChannelEmailThread::findOrFail($threadId);
        $this->activeMessageId = $messageId;
        $this->activeMessageDirection = $direction;
        $this->composeMode = false;
        $this->replyBody = '';
    }

    // Contact-Linking Methods
    public function openContactModal(): void
    {
        $this->modalShow = true;
    }

    public function closeContactModal(): void
    {
        $this->modalShow = false;
        $this->contactSearch = '';
    }

    public function selectContact($contactId): void
    {
        $contactLinkService = app(ContactLinkService::class);
        $contact = $contactLinkService->findContactById($contactId);
        
        if ($contact) {
            $this->selectedContactId = $contactId;
            $this->showEmailSelection = true;
            $this->contactSearch = '';
        }
    }

    public function selectEmailAddress($emailAddress): void
    {
        $this->selectedEmailAddress = $emailAddress;
        $this->compose['to'] = $emailAddress;
        $this->modalShow = false;
        $this->showEmailSelection = false;
        $this->selectedEmailAddress = '';
    }

    public function useNewEmailAddress(): void
    {
        $this->selectedContactId = null; // Kein Kontakt verlinkt
        $this->modalShow = false;
        $this->showEmailSelection = false;
        $this->selectedEmailAddress = '';
    }

    #[Computed]
    public function contacts()
    {
        $contactLinkService = app(ContactLinkService::class);
        return $contactLinkService->searchContacts($this->contactSearch);
    }

    #[Computed]
    public function threads()
    {
        return $this->account->threads()->latest()->get();
    }

    #[Computed]
    public function contextBlockHtml()
    {
        return $this->showContextDetails && !empty($this->context)
            ? $this->getContextBlock()->toHtml()
            : null;
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

        $token = app(EmailChannelPostmarkService::class)->send(
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

        // Automatisches Contact-Linking
        if ($token) {
            // Thread anhand des Tokens finden
            $thread = CommsChannelEmailThread::where('token', $token)->first();
            
            if ($thread) {
                $contactLinkService = app(ContactLinkService::class);
                
                if ($this->selectedContactId) {
                    // Kontakt wurde ausgewählt - verlinken und E-Mail-Adresse hinzufügen
                    $contact = $contactLinkService->findContactById($this->selectedContactId);
                    if ($contact) {
                        $contactLinkService->createContactLink($thread, $contact);
                        $contactLinkService->addEmailToContact($contact, $this->compose['to']);
                    }
                } else {
                    // Kein Kontakt ausgewählt - prüfen ob E-Mail bereits existiert
                    $existingContact = $contactLinkService->findContactsByEmailAddresses([$this->compose['to']])->first();
                    
                    if ($existingContact) {
                        // E-Mail-Adresse existiert bereits - bestehenden Kontakt verwenden
                        $contactLinkService->createContactLink($thread, $existingContact);
                    } else {
                        // E-Mail-Adresse existiert nicht - neuen Kontakt anlegen
                        $contact = $contactLinkService->createContact([
                            'first_name' => 'Unbekannt',
                            'last_name' => 'Kontakt',
                            'email' => $this->compose['to'],
                            'team_visible' => true,
                        ]);
                        $contactLinkService->createContactLink($thread, $contact);
                    }
                }
            }
        }

        $this->reset('compose', 'composeMode', 'selectedContactId', 'selectedEmailAddress');
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

    public function render()
    {
        return view('comms-channel-email::livewire.accounts.index');
    }
}