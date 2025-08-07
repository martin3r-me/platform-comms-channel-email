<div class="h-full d-flex flex-col gap-0">
    {{-- Channel-Navigation --}}
    <div class="border-bottom-1 border-bottom-solid border-bottom-muted bg-muted-5 py-1 d-flex items-center justify-between px-4">
        <div class="text-lg font-semibold text-secondary">
            {{ $account->label ?? $account->email }}
        </div>

        <div class="d-flex items-center gap-2">
            @if (!empty($context))
                <x-ui-button 
                    :variant="$showContextDetails ? 'info' : 'info-outline'"
                    size="sm"
                    wire:click="$toggle('showContextDetails')"
                    wire:key="btn-toggle-context"
                >
                    {{ $showContextDetails ? 'Kontext ausblenden' : 'Kontext einblenden' }}
                </x-ui-button>
            @endif

            @if (!$composeMode)
                <x-ui-button
                    variant="primary"
                    size="sm"
                    wire:click="startNewMessage"
                    wire:key="btn-start-new"
                >
                    Neue Nachricht
                </x-ui-button>
            @endif
        </div>
    </div>

    {{-- Kontextanzeige oberhalb --}}
    @if ($showContextDetails && !empty($context))
        <div class="bg-muted-5 border-bottom-1 border-bottom-solid border-bottom-muted p-4 text-xs text-muted-foreground">
            <div class="font-semibold uppercase text-muted text-xs mb-1">Kontext</div>
            <div class="d-flex flex-col gap-1">
                @if (!empty($context['model']))
                    <div><strong>Typ:</strong> {{ class_basename($context['model']) }}</div>
                @endif
                @if (!empty($context['modelId']))
                    <div><strong>ID:</strong> {{ $context['modelId'] }}</div>
                @endif
                @if (!empty($context['subject']))
                    <div><strong>Betreff:</strong> {{ $context['subject'] }}</div>
                @endif
                @if (!empty($context['description']))
                    <div><strong>Beschreibung:</strong> {!! nl2br(e($context['description'])) !!}</div>
                @endif
                @if (!empty($context['url']))
                    <div><strong>Link:</strong> <a href="{{ $context['url'] }}" class="text-primary hover:underline" target="_blank">{{ $context['url'] }}</a></div>
                @endif
                @if (!empty($context['meta']))
                    <div class="mt-1">
                        <strong>Metadaten:</strong>
                        <ul class="list-disc pl-4">
                            @foreach ($context['meta'] as $key => $value)
                                @if (!is_null($value))
                                    <li><strong>{{ $key }}:</strong> {{ $value }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Hauptinhalt --}}
    <div class="flex-grow-1 d-flex gap-2 overflow-hidden">
        {{-- Linke Spalte --}}
        <div class="w-80 flex-shrink-0 overflow-y-auto p-4 border-right-1 border-right-solid border-right-muted">
            @foreach ($this->threads as $thread)
                @php $threadKey = "thread-{$thread->id}"; @endphp

                <x-ui-grouped-list
                    :title="Str::limit($thread->subject ?: 'Kein Betreff', 20)"
                    icon="heroicon-o-envelope"
                    wire:key="{{ $threadKey }}"
                >
                    @foreach ($thread->timeline() as $message)
                        @php
                            $messageKey = "{$threadKey}-msg-{$message->id}";
                            $direction = ucfirst($message->direction);
                            $timestamp = \Carbon\Carbon::parse($message->occurred_at)->format('d.m.Y H:i');
                            $address = $message->direction === 'inbound' ? 'Von: ' . $message->from : 'An: ' . $message->to;
                        @endphp

                        <x-ui-grouped-list-item
                            :label="Str::limit($message->subject ?? 'Kein Betreff', 40)"
                            :subtitle="$address . ' · ' . $timestamp"
                            :selected="$activeMessageId === $message->id"
                            :badge="[
                                'text' => $direction,
                                'variant' => $message->direction === 'inbound' ? 'primary' : 'secondary',
                            ]"
                            wire:click="selectThread({{ $thread->id }}, {{ $message->id }}, '{{ $message->direction }}')"
                            wire:key="{{ $messageKey }}"
                        />
                    @endforeach
                </x-ui-grouped-list>
            @endforeach
        </div>

        {{-- Rechte Spalte --}}
        <div class="flex-grow-1 d-flex flex-col overflow-hidden">
            @if ($composeMode)
                <div class="p-4 overflow-y-auto" wire:key="composer-new-message">
                    <h3 class="text-md font-semibold mb-2">Neue Nachricht</h3>
                    <div class="d-flex flex-col gap-3">
                        <div>
                            <x-ui-input-text
                                name="compose.to"
                                label="Empfänger"
                                wire:model.defer="compose.to"
                                placeholder="E-Mail-Adresse eingeben"
                                type="email"
                                size="lg"
                            />
                            
                            <div class="mt-2 d-flex justify-end">
                                <x-ui-button 
                                    variant="secondary-outline" 
                                    size="sm"
                                    wire:click="openContactModal"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    Kontakt auswählen
                                </x-ui-button>
                            </div>
                            
                            @if($selectedContactId)
                                @php 
                                    $contactLinkService = app(\Platform\Crm\Services\ContactLinkService::class);
                                    $contact = $contactLinkService->findContactById($selectedContactId);
                                @endphp
                                @if($contact)
                                    <div class="mt-3 p-3 bg-success-10 border border-success rounded-lg">
                                        <div class="d-flex items-center gap-3">
                                            <div class="w-8 h-8 bg-success rounded-full d-flex items-center justify-center text-on-success text-sm font-semibold">
                                                {{ strtoupper(substr($contact->first_name, 0, 1) . substr($contact->last_name, 0, 1)) }}
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="font-medium text-success">{{ $contact->full_name }}</div>
                                                <div class="text-sm text-muted">Kontakt verlinkt</div>
                                            </div>
                                            <x-ui-button 
                                                variant="danger-outline" 
                                                size="xs"
                                                wire:click="$set('selectedContactId', null)"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </x-ui-button>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div>
                            <x-ui-input-text
                                name="compose.subject"
                                label="Betreff"
                                wire:model.defer="compose.subject"
                                placeholder="Betreff eingeben"
                                size="lg"
                            />
                        </div>

                        <div>
                            <x-ui-input-textarea
                                name="compose.body"
                                label="Nachricht"
                                wire:model.defer="compose.body"
                                placeholder="Ihre Nachricht..."
                                rows="8"
                                size="lg"
                            />
                        </div>

                        {{-- Kontextblock als Vorschau --}}
                        @if ($showContextDetails && $this->contextBlockHtml)
                            <div class="border border-muted rounded p-3 bg-muted-10 text-xs text-muted-foreground">
                                {!! $this->contextBlockHtml !!}
                            </div>
                        @endif

                        <div class="d-flex justify-end gap-2">
                            <x-ui-button variant="muted" wire:click="$set('composeMode', false)">Abbrechen</x-ui-button>
                            <x-ui-button variant="primary" wire:click="sendNewMessage" wire:key="btn-send-new">Senden</x-ui-button>
                        </div>
                    </div>
                </div>
            @elseif ($activeThread && $activeMessageId)
			    @php
			        $message = $activeThread->timeline()->firstWhere('id', $activeMessageId);
			    @endphp

			    @if ($message)
			        <div class="d-flex flex-col flex-grow-1 overflow-hidden bg-white border rounded" wire:key="message-view-{{ $message->id }}">
			            <div class="flex-grow-1 overflow-y-auto p-4 bg-muted-5">
			                <div class="d-flex justify-between items-center mb-2">
			                    <div class="text-sm font-semibold text-primary">
			                        {{ $message->subject ?? 'Kein Betreff' }}
			                    </div>
			                    <div class="text-xs text-muted-foreground">
			                        {{ ucfirst($message->direction) }} am {{ \Carbon\Carbon::parse($message->occurred_at)->format('d.m.Y H:i') }}
			                    </div>
			                </div>

			                <div class="text-xs text-muted-foreground mb-2">
			                    @if ($message->direction === 'inbound')
			                        Von: {{ $message->from }}
			                    @else
			                        An: {{ $message->to }}
			                    @endif
			                </div>

			                {{-- Contact-Links Sektion --}}
			                <div class="mb-4">
			                    <x-comms-channel-email::contact-links 
			                        :thread="$activeThread" 
			                        :show-manage="true"
			                    />
			                </div>

			                <div class="prose prose-sm max-w-none text-sm leading-relaxed">
			                    {!! $message->html_body ?: nl2br(e($message->text_body)) !!}
			                </div>
			            </div>

			            <div class="border-top p-3 bg-white">
			                <label class="text-sm font-medium text-muted-foreground mb-1 block">Antwort</label>
			                <textarea rows="4" wire:model.defer="replyBody" class="form-control w-full mb-2"></textarea>
			                <div class="d-flex justify-end">
			                    <x-ui-button variant="primary" wire:click="sendReply" wire:key="btn-send-reply">Antwort senden</x-ui-button>
			                </div>
			            </div>
			        </div>
			    @endif
			@else
			    <div class="p-4 text-muted-foreground text-sm italic" wire:key="placeholder-no-selection">
			        Bitte wähle einen Thread oder klicke auf „Neu“.
			    </div>
            @endif
        </div>
    </div>

    {{-- Contact-Auswahl Modal --}}
    <x-ui-modal wire:model="modalShow" title="Kontakt auswählen" size="lg">
        <div class="d-flex flex-col gap-6">
            <!-- Suchfeld -->
            <div>
                <x-ui-input-text
                    name="contactSearch"
                    label="Kontakte durchsuchen"
                    wire:model.live="contactSearch"
                    placeholder="Name oder E-Mail-Adresse eingeben..."
                    size="lg"
                />
            </div>

            <!-- Kontakte Liste -->
            <div class="max-h-96 overflow-y-auto">
                @if($this->contacts->count() > 0)
                    <div class="d-flex flex-col gap-3">
                        @foreach($this->contacts as $contact)
                            <div 
                                class="p-4 border border-muted rounded-lg cursor-pointer hover:bg-muted-5 transition-colors duration-200 hover:border-primary"
                                wire:click="selectContact({{ $contact->id }})"
                            >
                                <div class="d-flex items-center gap-4">
                                    <!-- Avatar -->
                                    <div class="w-12 h-12 bg-primary rounded-full d-flex items-center justify-center text-on-primary text-lg font-semibold flex-shrink-0">
                                        {{ strtoupper(substr($contact->first_name, 0, 1) . substr($contact->last_name, 0, 1)) }}
                                    </div>
                                    
                                    <!-- Kontakt-Info -->
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex items-center gap-2 mb-1">
                                            <h3 class="text-lg font-semibold text-secondary truncate">
                                                {{ $contact->full_name }}
                                            </h3>
                                            @if($contact->contactStatus)
                                                <x-ui-badge variant="primary" size="sm">
                                                    {{ $contact->contactStatus->name }}
                                                </x-ui-badge>
                                            @endif
                                        </div>
                                        
                                        @if($contact->emailAddresses->count() > 0)
                                            <div class="d-flex flex-col gap-1">
                                                @foreach($contact->emailAddresses->take(2) as $emailAddress)
                                                    <div class="d-flex items-center gap-2 text-sm text-muted">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                                        </svg>
                                                        <span class="truncate">{{ $emailAddress->email_address }}</span>
                                                    </div>
                                                @endforeach
                                                @if($contact->emailAddresses->count() > 2)
                                                    <div class="text-xs text-muted">
                                                        +{{ $contact->emailAddresses->count() - 2 }} weitere E-Mail-Adressen
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-sm text-muted">Keine E-Mail-Adressen</div>
                                        @endif
                                    </div>
                                    
                                    <!-- Pfeil -->
                                    <div class="text-muted">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif($contactSearch)
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-muted-10 rounded-full d-flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary mb-2">Keine Kontakte gefunden</h3>
                        <p class="text-muted">Für "{{ $contactSearch }}" wurden keine Kontakte gefunden.</p>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-primary-10 rounded-full d-flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary mb-2">Kontakte durchsuchen</h3>
                        <p class="text-muted">Geben Sie einen Namen oder eine E-Mail-Adresse ein, um Kontakte zu finden.</p>
                    </div>
                @endif
            </div>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button variant="muted" wire:click="$set('modalShow', false)">Abbrechen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- E-Mail-Adressen-Auswahl Modal --}}
    <x-ui-modal wire:model="showEmailSelection" title="E-Mail-Adresse auswählen" size="md">
        @if($selectedContactId)
            @php 
                $contactLinkService = app(\Platform\Crm\Services\ContactLinkService::class);
                $contact = $contactLinkService->findContactById($selectedContactId);
            @endphp
            
            @if($contact)
                <div class="d-flex flex-col gap-6">
                    <!-- Kontakt-Info Header -->
                    <div class="p-4 bg-muted-5 rounded-lg border border-muted">
                        <div class="d-flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary rounded-full d-flex items-center justify-center text-on-primary text-lg font-semibold">
                                {{ strtoupper(substr($contact->first_name, 0, 1) . substr($contact->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-secondary">{{ $contact->full_name }}</h3>
                                <p class="text-sm text-muted">Wählen Sie eine E-Mail-Adresse aus</p>
                            </div>
                        </div>
                    </div>

                    <!-- E-Mail-Adressen Liste -->
                    <div class="d-flex flex-col gap-3">
                        @if($contact->emailAddresses->count() > 0)
                            @foreach($contact->emailAddresses as $emailAddress)
                                <div 
                                    class="p-4 border border-muted rounded-lg cursor-pointer hover:bg-muted-5 transition-colors duration-200 hover:border-primary"
                                    wire:click="selectEmailAddress('{{ $emailAddress->email_address }}')"
                                >
                                    <div class="d-flex items-center gap-4">
                                        <div class="w-10 h-10 bg-success rounded-full d-flex items-center justify-center text-on-success">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="font-medium text-secondary">{{ $emailAddress->email_address }}</div>
                                            @if($emailAddress->emailType)
                                                <div class="text-sm text-muted">{{ $emailAddress->emailType->name }}</div>
                                            @endif
                                        </div>
                                        <div class="text-muted">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        <!-- Neue E-Mail-Adresse Option -->
                        <div 
                            class="p-4 border-2 border-dashed border-muted rounded-lg cursor-pointer hover:bg-muted-5 transition-colors duration-200 hover:border-primary"
                            wire:click="useNewEmailAddress()"
                        >
                            <div class="d-flex items-center gap-4">
                                <div class="w-10 h-10 bg-muted rounded-full d-flex items-center justify-center text-muted">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="font-medium text-secondary">Neue E-Mail-Adresse verwenden</div>
                                    <div class="text-sm text-muted">Geben Sie eine neue E-Mail-Adresse ein</div>
                                </div>
                                <div class="text-muted">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-danger-10 rounded-full d-flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-secondary mb-2">Kontakt nicht gefunden</h3>
                    <p class="text-muted">Der ausgewählte Kontakt konnte nicht geladen werden.</p>
                </div>
            @endif
        @else
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-muted-10 rounded-full d-flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-secondary mb-2">Kein Kontakt ausgewählt</h3>
                <p class="text-muted">Bitte wählen Sie zuerst einen Kontakt aus.</p>
            </div>
        @endif

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button variant="muted" wire:click="$set('showEmailSelection', false)">Abbrechen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</div>