<div>
    <x-ui-modal
        wire:model="showModal"
        size="lg"
        header="Kontakte verlinken"
        :footer="null"
    >
        <div class="space-y-4">
            <!-- Suchfeld -->
            <div>
                <x-ui-input-text
                    name="search"
                    label="Kontakte suchen"
                    wire:model.live="search"
                    placeholder="Name oder E-Mail eingeben..."
                />
            </div>

            <!-- Kontakte Liste -->
            <div class="max-h-96 overflow-y-auto space-y-2">
                @foreach($this->contacts as $contact)
                    <div class="d-flex items-center justify-between p-3 bg-surface rounded-lg border border-muted hover:bg-muted-5 transition-colors">
                        <div class="d-flex items-center gap-3">
                            <x-ui-input-checkbox
                                name="selectedContacts"
                                value="{{ $contact->id }}"
                                wire:model.live="selectedContacts"
                                wire:click="toggleContact({{ $contact->id }})"
                            />
                            
                            <div class="w-8 h-8 bg-primary rounded-full d-flex items-center justify-center text-on-primary text-sm font-semibold">
                                {{ strtoupper(substr($contact->first_name, 0, 1) . substr($contact->last_name, 0, 1)) }}
                            </div>
                            
                            <div>
                                <div class="font-medium text-body">{{ $contact->full_name }}</div>
                                @if($contact->emailAddresses->first())
                                    <div class="text-sm text-muted">{{ $contact->emailAddresses->first()->email_address }}</div>
                                @endif
                            </div>
                        </div>
                        
                        <x-ui-badge variant="primary" size="sm">
                            {{ $contact->contactStatus->name }}
                        </x-ui-badge>
                    </div>
                @endforeach
                
                @if($this->contacts->isEmpty())
                    <div class="p-4 text-center text-muted">
                        @if($search)
                            <p>Keine Kontakte gefunden für "{{ $search }}"</p>
                        @else
                            <p>Keine Kontakte verfügbar</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Aktionsbuttons -->
            <div class="d-flex justify-end gap-2 pt-4">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    wire:click="closeModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button 
                    type="button" 
                    variant="primary"
                    wire:click="saveContacts"
                >
                    Kontakte verlinken
                </x-ui-button>
            </div>
        </div>
    </x-ui-modal>
</div> 