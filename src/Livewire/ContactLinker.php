<?php

namespace Platform\Comms\ChannelEmail\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Platform\Comms\ChannelEmail\Models\CommsChannelEmailThread;
use Platform\Crm\Services\ContactLinkService;

class ContactLinker extends Component
{
    public CommsChannelEmailThread $thread;
    public $search = '';
    public $selectedContacts = [];
    public $showModal = false;

    #[On('openContactLinker')]
    public function openModal($data)
    {
        $this->thread = CommsChannelEmailThread::findOrFail($data['threadId']);
        $this->showModal = true;
        
        $contactLinkService = app(ContactLinkService::class);
        $this->selectedContacts = $contactLinkService->getLinkedContacts($this->thread)->pluck('id')->toArray();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->search = '';
        $this->selectedContacts = [];
    }

    #[Computed]
    public function contacts()
    {
        $contactLinkService = app(ContactLinkService::class);
        return $contactLinkService->searchContacts($this->search, 20);
    }

    public function toggleContact($contactId)
    {
        if (in_array($contactId, $this->selectedContacts)) {
            $this->selectedContacts = array_diff($this->selectedContacts, [$contactId]);
        } else {
            $this->selectedContacts[] = $contactId;
        }
    }

    public function saveContacts()
    {
        $contactLinkService = app(ContactLinkService::class);
        
        // Alle bestehenden Links entfernen
        $contactLinkService->removeAllContactLinks($this->thread);
        
        // Neue Links erstellen
        foreach ($this->selectedContacts as $contactId) {
            $contact = $contactLinkService->findContactById($contactId);
            if ($contact) {
                $contactLinkService->createContactLink($this->thread, $contact);
            }
        }
        
        $this->closeModal();
        $this->dispatch('contactsUpdated');
    }

    public function render()
    {
        return view('comms-channel-email::livewire.contact-linker');
    }
} 