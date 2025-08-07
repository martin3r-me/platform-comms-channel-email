@props([
    'thread' => null,
    'showManage' => false
])

@php
    $contactLinkService = app(\Platform\Crm\Services\ContactLinkService::class);
    $contacts = $contactLinkService->getLinkedContacts($thread);
@endphp

<div class="contact-links-section">
    <div class="d-flex justify-between items-center mb-4">
        <div class="d-flex items-center gap-2">
            <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h4 class="text-lg font-semibold text-secondary">Verlinkte Kontakte</h4>
            @if($contacts->count() > 0)
                <x-ui-badge variant="primary" size="sm" :counter="$contacts->count()">
                    {{ $contacts->count() }}
                </x-ui-badge>
            @endif
        </div>
        
        @if($showManage)
            <x-ui-button 
                size="sm" 
                variant="primary-outline"
                wire:click="$dispatch('openContactLinker', { threadId: {{ $thread->id }} })"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Kontakte verwalten
            </x-ui-button>
        @endif
    </div>

    @if($contacts->count() > 0)
        <div class="d-flex flex-col gap-3">
            @foreach($contacts as $contact)
                <div class="d-flex justify-between items-center p-4 bg-surface rounded-lg border border-muted hover:bg-muted-5 transition-colors duration-200">
                    <div class="d-flex items-center gap-4">
                        <div class="w-10 h-10 bg-primary rounded-full d-flex items-center justify-center text-on-primary text-sm font-semibold">
                            {{ strtoupper(substr($contact->first_name, 0, 1) . substr($contact->last_name, 0, 1)) }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-medium text-secondary">{{ $contact->full_name }}</div>
                            @if($contact->emailAddresses->first())
                                <div class="text-sm text-muted">{{ $contact->emailAddresses->first()->email_address }}</div>
                            @endif
                        </div>
                    </div>
                    
                    @if($showManage)
                        <x-ui-button 
                            size="sm" 
                            variant="danger-outline"
                            wire:click="detachContact({{ $contact->id }})"
                            wire:confirm="Kontakt wirklich entfernen?"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </x-ui-button>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="p-8 text-center bg-muted-5 rounded-lg border-2 border-dashed border-muted">
            <div class="w-16 h-16 bg-muted-10 rounded-full d-flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-secondary mb-2">Keine Kontakte verlinkt</h3>
            <p class="text-muted mb-4">Dieser Thread hat noch keine verlinkten Kontakte.</p>
            @if($showManage)
                <x-ui-button 
                    variant="primary-outline" 
                    size="sm"
                    wire:click="$dispatch('openContactLinker', { threadId: {{ $thread->id }} })"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Kontakte hinzufügen
                </x-ui-button>
            @endif
        </div>
    @endif
</div> 