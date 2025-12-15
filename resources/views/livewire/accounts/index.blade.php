<div class="h-full d-flex flex-col" x-data="{ activeTab: '{{ $activeTab ?? 'messages' }}' }">
    {{-- Channel-Navigation mit Tabs --}}
    <div class="border-bottom-1 border-bottom-solid border-bottom-muted bg-muted-5 flex-shrink-0">
        <div class="d-flex items-center justify-between px-4 py-3">
            <div class="d-flex items-center gap-4">
                @if (($activeTab ?? 'messages') === 'messages')
                    <x-ui-button
                        variant="primary"
                        size="sm"
                        wire:click="startNewMessage"
                        wire:key="btn-start-new"
                        iconOnly
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                    </x-ui-button>
                @endif

                <div class="text-lg font-semibold text-secondary">
                    {{ $account->label ?? $account->email }}
                </div>
                
                {{-- Tab-Navigation --}}
                <div class="d-flex bg-white rounded-lg p-1 border border-muted">
                    <button 
                        @click="activeTab = 'messages'; $wire.set('activeTab', 'messages')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="activeTab === 'messages' 
                            ? 'bg-primary text-on-primary shadow-sm' 
                            : 'text-muted hover:text-secondary'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-envelope', 'w-4 h-4')
                            <span>Nachrichten</span>
                        </div>
                    </button>
                    <button 
                        @click="activeTab = 'settings'; $wire.set('activeTab', 'settings')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="activeTab === 'settings' 
                            ? 'bg-primary text-on-primary shadow-sm' 
                            : 'text-muted hover:text-secondary'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                            <span>Einstellungen</span>
                        </div>
                    </button>

                </div>
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
            </div>
        </div>
    </div>

    {{-- Kontextanzeige oberhalb --}}
    @if ($showContextDetails && !empty($context))
        <div class="bg-muted-5 border-bottom-1 border-bottom-solid border-bottom-muted p-4 text-xs text-muted-foreground flex-shrink-0">
            <div class="font-semibold uppercase text-muted text-xs mb-2">Kontext</div>
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

    {{-- Tab-Inhalte --}}
    <div x-show="activeTab === 'messages'" x-cloak>
        {{-- Nachrichten-Tab --}}
        <div class="flex-grow-1 d-flex gap-0 overflow-hidden">
            {{-- Linke Spalte: Thread-Liste --}}
            <div class="w-96 flex-shrink-0 overflow-y-auto border-right-1 border-right-solid border-right-muted">
                <div class="p-4">
                    <h3 class="text-sm text-muted-foreground font-semibold uppercase mb-4">Nachrichten</h3>
                    
                    @if($this->threads->count() > 0)
                        <div class="d-flex flex-col gap-2">
                            @foreach ($this->threads as $thread)
                                @php 
                                    $threadKey = "thread-{$thread->id}";
                                    $latestMessage = $thread->timeline()->first();
                                    $isActive = $activeThread && $activeThread->id === $thread->id;
                                @endphp

                                <div 
                                    class="p-3 rounded-lg cursor-pointer transition-all duration-200 border border-transparent hover:border-primary hover:bg-primary-5"
                                    :class="'{{ $isActive ? 'bg-primary-10 border-primary' : '' }}'"
                                    wire:click="selectThread({{ $thread->id }}, {{ $latestMessage?->id }}, '{{ $latestMessage?->direction }}')"
                                    wire:key="{{ $threadKey }}"
                                >
                                    <div class="d-flex items-start gap-3">
                                        <div class="w-10 h-10 bg-primary rounded-full d-flex items-center justify-center text-on-primary text-sm font-semibold flex-shrink-0">
                                            @svg('heroicon-o-envelope', 'w-5 h-5')
                                        </div>
                                        
                                        <div class="flex-grow-1 min-w-0">
                                            {{-- Erste Zeile: Empfänger/Absender --}}
                                            <div class="font-medium text-secondary truncate">
                                                @if($latestMessage)
                                                    @if($latestMessage->direction === 'inbound')
                                                        Von: {{ $latestMessage->from }}
                                                    @else
                                                        An: {{ $latestMessage->to }}
                                                    @endif
                                                @else
                                                    Keine Nachrichten
                                                @endif
                                            </div>
                                            
                                            {{-- Zweite Zeile: Betreff --}}
                                            <div class="text-sm text-muted truncate mt-1">
                                                {{ $thread->subject ?: 'Kein Betreff' }}
                                            </div>
                                            
                                            {{-- Dritte Zeile: Zeitstempel und Richtung --}}
                                            @if($latestMessage)
                                                <div class="d-flex items-center gap-2 mt-1">
                                                    <div class="text-xs text-muted">
                                                        {{ \Carbon\Carbon::parse($latestMessage->occurred_at)->format('d.m.Y H:i') }}
                                                    </div>
                                                    <x-ui-badge 
                                                        variant="{{ $latestMessage->direction === 'inbound' ? 'primary' : 'secondary' }}" 
                                                        size="xs"
                                                    >
                                                        {{ $latestMessage->direction === 'inbound' ? 'Eingehend' : 'Ausgehend' }}
                                                    </x-ui-badge>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-muted-10 rounded-full d-flex items-center justify-center mx-auto mb-4">
                                @svg('heroicon-o-envelope', 'w-8 h-8 text-muted')
                            </div>
                            <h3 class="text-lg font-semibold text-secondary mb-2">Keine Nachrichten</h3>
                            <p class="text-muted">Es wurden noch keine Nachrichten empfangen oder gesendet.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Rechte Spalte: Message-Viewer oder Composer --}}
            <div class="flex-grow-1 d-flex flex-col overflow-hidden">
                @if ($composeMode)
                    {{-- Compose-Modus --}}
                    <div class="p-6 overflow-y-auto" wire:key="composer-new-message">
                        <h3 class="text-lg font-semibold mb-4">Neue Nachricht</h3>
                        <div class="d-flex flex-col gap-4">
                            <div>
                                <x-ui-input-text
                                    name="compose.to"
                                    label="Empfänger"
                                    wire:model.defer="compose.to"
                                    placeholder="E-Mail-Adresse eingeben"
                                    type="email"
                                    size="lg"
                                />
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
                                    rows="12"
                                    size="lg"
                                />
                            </div>

                            {{-- Kontextblock als Vorschau --}}
                            @if ($showContextDetails && $this->contextBlockHtml)
                                <div class="border border-muted rounded p-4 bg-muted-10 text-xs text-muted-foreground">
                                    <div class="font-semibold mb-2">Kontext wird angehängt:</div>
                                    {!! $this->contextBlockHtml !!}
                                </div>
                            @endif

                            <div class="d-flex justify-end gap-3">
                                <x-ui-button variant="muted" wire:click="$set('composeMode', false)">Abbrechen</x-ui-button>
                                <x-ui-button variant="primary" wire:click="sendNewMessage" wire:key="btn-send-new">
                                    <div class="d-flex items-center gap-2">
                                        @svg('heroicon-o-paper-airplane', 'w-4 h-4')
                                        <span>Senden</span>
                                    </div>
                                </x-ui-button>
                            </div>
                        </div>
                    </div>
                @elseif ($activeThread)
                    {{-- Conversation-Viewer: kompletter Verlauf --}}
                    <div class="d-flex flex-col flex-grow-1 overflow-hidden bg-white">
                        {{-- Header: Thread-Infos --}}
                        <div class="border-bottom-1 border-bottom-solid border-bottom-muted p-4 flex-shrink-0">
                            <div class="d-flex justify-between items-start">
                                <div class="flex-grow-1">
                                    <h2 class="text-lg font-semibold text-secondary mb-1">
                                        {{ $activeThread->subject ?? 'Kein Betreff' }}
                                    </h2>
                                    <div class="text-xs text-muted">Verlauf</div>
                                </div>
                            </div>
                        </div>

                        {{-- Verlauf --}}
                        <div class="flex-grow-1 overflow-y-auto p-6 space-y-6">
                            @php $messages = $activeThread->timeline()->sortBy('occurred_at'); @endphp
                            @forelse($messages as $message)
                                <div class="d-flex flex-col gap-2">
                                    <div class="d-flex items-center justify-between">
                                        <div class="d-flex items-center gap-3 text-sm text-muted">
                                            <div class="d-flex items-center gap-2">
                                                @if ($message->direction === 'inbound')
                                                    @svg('heroicon-o-arrow-down', 'w-4 h-4 text-primary')
                                                    <span>Von: {{ $message->from }}</span>
                                                @else
                                                    @svg('heroicon-o-arrow-up', 'w-4 h-4 text-secondary')
                                                    <span>An: {{ $message->to }}</span>
                                                @endif
                                            </div>
                                            <div class="d-flex items-center gap-2">
                                                @svg('heroicon-o-clock', 'w-4 h-4')
                                                <span>{{ \Carbon\Carbon::parse($message->occurred_at)->format('d.m.Y H:i') }}</span>
                                            </div>
                                            <x-ui-badge 
                                                variant="{{ $message->direction === 'inbound' ? 'primary' : 'secondary' }}" 
                                                size="xs"
                                            >
                                                {{ $message->direction === 'inbound' ? 'Eingehend' : 'Ausgehend' }}
                                            </x-ui-badge>
                                        </div>
                                    </div>

                                    <div class="prose prose-sm max-w-none text-sm leading-relaxed">
                                        {!! $message->html_body ?: nl2br(e($message->text_body)) !!}
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted">Keine Nachrichten im Thread.</div>
                            @endforelse
                        </div>

                        {{-- Reply-Bereich --}}
                        <div class="border-top-1 border-top-solid border-top-muted p-4 flex-shrink-0">
                            <div class="d-flex flex-col gap-3">
                                <label class="text-sm font-medium text-muted-foreground">Antwort</label>
                                <textarea 
                                    rows="4" 
                                    wire:model.defer="replyBody" 
                                    class="form-control w-full p-3 border border-muted rounded-lg resize-none focus:border-primary focus:ring-1 focus:ring-primary"
                                    placeholder="Ihre Antwort..."
                                ></textarea>
                                <div class="d-flex justify-end">
                                    <x-ui-button variant="primary" wire:click="sendReply" wire:key="btn-send-reply">
                                        <div class="d-flex items-center gap-2">
                                            @svg('heroicon-o-paper-airplane', 'w-4 h-4')
                                            <span>Antwort senden</span>
                                        </div>
                                    </x-ui-button>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Platzhalter --}}
                    <div class="flex-grow-1 d-flex items-center justify-center">
                        <div class="text-center">
                            <div class="w-24 h-24 bg-muted-10 rounded-full d-flex items-center justify-center mx-auto mb-6">
                                @svg('heroicon-o-envelope', 'w-12 h-12 text-muted')
                            </div>
                            <h3 class="text-xl font-semibold text-secondary mb-2">Nachricht auswählen</h3>
                            <p class="text-muted">Wählen Sie eine Nachricht aus der Liste aus oder erstellen Sie eine neue Nachricht.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        </div> {{-- Ende Nachrichten-Tab --}}
    </div>

    {{-- Settings-Tab --}}
    <div x-show="activeTab === 'settings'" x-cloak>
        <div class="flex-grow-1 overflow-y-auto">
             <div class="d-flex h-full">
                 {{-- Linke Spalte: Einstellungen --}}
                 <div class="flex-grow-1 overflow-y-auto p-6">
                     <div class="max-w-2xl">
                         <h2 class="text-2xl font-bold text-secondary mb-6">E-Mail-Konto Einstellungen</h2>
                         
                         <div class="d-flex flex-col gap-6">


                             {{-- Account-Informationen --}}
                             <div class="bg-white rounded-lg border border-muted p-6">
                                 <h3 class="text-lg font-semibold text-secondary mb-4">Account-Informationen</h3>
                                 <div class="d-flex flex-col gap-4">
                                     <div>
                                         <label class="text-sm font-medium text-muted-foreground mb-1 block">E-Mail-Adresse</label>
                                         <div class="text-secondary font-medium">{{ $account->email }}</div>
                                     </div>
                                     
                                     {{-- Anzeigename (editierbar) --}}
                                     <div>
                                         @if(empty($editName))
                                             <div class="d-flex items-center justify-between">
                                                 <div>
                                                     <label class="text-sm font-medium text-muted-foreground mb-1 block">Anzeigename</label>
                                                     <div class="text-secondary font-medium">{{ $account->name ?: 'Nicht gesetzt' }}</div>
                                                 </div>
                                                 <x-ui-button 
                                                     variant="secondary-outline" 
                                                     size="sm"
                                                     wire:click="startEditName"
                                                 >
                                                     <div class="d-flex items-center gap-2">
                                                         @svg('heroicon-o-pencil', 'w-4 h-4')
                                                         <span>Bearbeiten</span>
                                                     </div>
                                                 </x-ui-button>
                                             </div>
                                         @else
                                             <div>
                                                 <label class="text-sm font-medium text-muted-foreground mb-1 block">Anzeigename</label>
                                                 <div class="d-flex gap-2">
                                                     <x-ui-input-text
                                                         name="editName"
                                                         wire:model="editName"
                                                         placeholder="Anzeigename eingeben..."
                                                         class="flex-grow-1"
                                                     />
                                                     <x-ui-button 
                                                         variant="primary" 
                                                         size="sm"
                                                         wire:click="saveName"
                                                     >
                                                         <div class="d-flex items-center gap-2">
                                                             @svg('heroicon-o-check', 'w-4 h-4')
                                                             <span>Speichern</span>
                                                         </div>
                                                     </x-ui-button>
                                                     <x-ui-button 
                                                         variant="muted" 
                                                         size="sm"
                                                         wire:click="cancelEditName"
                                                     >
                                                         <div class="d-flex items-center gap-2">
                                                             @svg('heroicon-o-x-mark', 'w-4 h-4')
                                                             <span>Abbrechen</span>
                                                         </div>
                                                     </x-ui-button>
                                                 </div>
                                             </div>
                                         @endif
                                     </div>
                                     
                                     <div>
                                         <label class="text-sm font-medium text-muted-foreground mb-1 block">Team</label>
                                         <div class="text-secondary font-medium">{{ $account->team?->name ?: 'Kein Team zugeordnet' }}</div>
                                     </div>
                                     
                                     {{-- Ownership Type (editierbar) --}}
                                     <div>
                                         <label class="text-sm font-medium text-muted-foreground mb-1 block">Konto-Typ</label>
                                         <x-ui-input-select
                                             name="account.ownership_type"
                                             wire:model.live="account.ownership_type"
                                             :options="collect([
                                                 ['id' => 'team', 'name' => 'Team-Konto (alle Team-Mitglieder haben Zugriff)'],
                                                 ['id' => 'user', 'name' => 'Privates Konto (nur Sie haben Zugriff)']
                                             ])"
                                             optionValue="id"
                                             optionLabel="name"
                                             size="md"
                                         />
                                         <div class="text-xs text-muted mt-1">
                                             @if($account->ownership_type === 'team')
                                                 Das Konto wird dem gesamten Team zur Verfügung gestellt.
                                             @else
                                                 Das Konto ist privat und nur für Sie zugänglich.
                                             @endif
                                         </div>
                                     </div>
                                     
                                     {{-- Privater Besitzer (nur bei privatem Konto) --}}
                                     @if($account->ownership_type === 'user')
                                         <div>
                                             <label class="text-sm font-medium text-muted-foreground mb-1 block">Privater Besitzer</label>
                                             <x-ui-input-select
                                                 name="account.user_id"
                                                 wire:model.live="account.user_id"
                                                 :options="collect([
                                                     ['id' => auth()->user()->id, 'name' => auth()->user()->fullname . ' (Sie)']
                                                 ])"
                                                 optionValue="id"
                                                 optionLabel="name"
                                                 size="md"
                                             />
                                         </div>
                                     @endif
                                     
                                     <div>
                                         <label class="text-sm font-medium text-muted-foreground mb-1 block">Erstellt von</label>
                                         <div class="text-secondary font-medium">{{ $account->createdByUser?->fullname }}</div>
                                     </div>
                                     
                                     <div>
                                         <label class="text-sm font-medium text-muted-foreground mb-1 block">Typ</label>
                                         <div class="text-secondary font-medium">
                                             @if($account->ownership_type === 'team')
                                                 <x-ui-badge variant="primary" size="sm">Team-Konto</x-ui-badge>
                                             @else
                                                 <x-ui-badge variant="secondary" size="sm">Privates Konto</x-ui-badge>
                                                 @if($account->user)
                                                     <span class="text-sm text-muted ml-2">({{ $account->user->fullname }})</span>
                                                 @endif
                                             @endif
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             {{-- Statistiken --}}
                             <div class="bg-white rounded-lg border border-muted p-6">
                                 <h3 class="text-lg font-semibold text-secondary mb-4">Statistiken</h3>
                                 <div class="grid grid-cols-2 gap-4">
                                     <div class="text-center p-4 bg-primary-10 rounded-lg">
                                         <div class="text-2xl font-bold text-primary">{{ $this->threads->count() }}</div>
                                         <div class="text-sm text-muted">Nachrichten-Threads</div>
                                     </div>
                                     <div class="text-center p-4 bg-secondary-10 rounded-lg">
                                         <div class="text-2xl font-bold text-secondary">{{ $this->threads->sum(function($thread) { return $thread->timeline()->count(); }) }}</div>
                                         <div class="text-sm text-muted">Gesamt Nachrichten</div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>

                 {{-- Rechte Spalte: Benutzer-Verwaltung --}}
                 <div class="w-96 flex-shrink-0 border-left-1 border-left-solid border-left-muted">
                     <div class="p-6">
                         <div class="d-flex items-center justify-between mb-4">
                             <h3 class="text-lg font-semibold text-secondary">Zugriffsberechtigte</h3>
                             <x-ui-button 
                                 variant="primary" 
                                 size="sm"
                                 wire:click="$set('showUserModal', true)"
                             >
                                 <div class="d-flex items-center gap-2">
                                     @svg('heroicon-o-plus', 'w-4 h-4')
                                     <span>Hinzufügen</span>
                                 </div>
                             </x-ui-button>
                         </div>

                         {{-- Aktuelle Benutzer --}}
                         <div class="d-flex flex-col gap-3">
                             @if($this->sharedUsers->count() > 0)
                                 @foreach($this->sharedUsers as $user)
                                     <div class="d-flex items-center justify-between p-3 bg-muted-5 rounded-lg">
                                         <div class="d-flex items-center gap-3">
                                             <div class="w-8 h-8 bg-primary rounded-full d-flex items-center justify-center text-on-primary text-sm font-semibold">
                                                 {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                             </div>
                                             <div>
                                                 <div class="font-medium text-secondary">{{ $user->fullname }}</div>
                                                 <div class="text-xs text-muted">{{ $user->email }}</div>
                                             </div>
                                         </div>
                                         <x-ui-button 
                                             variant="danger-outline" 
                                             size="xs"
                                             wire:click="removeUser({{ $user->id }})"
                                         >
                                             <div class="d-flex items-center gap-1">
                                                 @svg('heroicon-o-trash', 'w-3 h-3')
                                                 <span>Entfernen</span>
                                             </div>
                                         </x-ui-button>
                                     </div>
                                 @endforeach
                             @else
                                 <div class="text-center py-8">
                                     <div class="w-16 h-16 bg-muted-10 rounded-full d-flex items-center justify-center mx-auto mb-4">
                                         @svg('heroicon-o-users', 'w-8 h-8 text-muted')
                                     </div>
                                     <h4 class="text-lg font-semibold text-secondary mb-2">Keine Benutzer</h4>
                                     <p class="text-muted">Fügen Sie Benutzer hinzu, um ihnen Zugriff auf dieses Konto zu gewähren.</p>
                                 </div>
                             @endif
                         </div>
                     </div>
                 </div>
             </div>
         </div>

         

          {{-- Benutzer hinzufügen Modal --}}
          <x-ui-modal wire:model="showUserModal" title="Benutzer hinzufügen" size="md">
             <div class="d-flex flex-col gap-6">
                 <!-- Suchfeld -->
                 <div>
                     <x-ui-input-text
                         name="userSearch"
                         label="Benutzer durchsuchen"
                         wire:model.live="userSearch"
                         placeholder="Name oder E-Mail-Adresse eingeben..."
                         size="lg"
                     />
                 </div>

                 <!-- Verfügbare Benutzer -->
                 <div class="max-h-96 overflow-y-auto">
                     @if($this->availableUsers->count() > 0)
                         <div class="d-flex flex-col gap-3">
                             @foreach($this->availableUsers as $user)
                                 <div 
                                     class="p-4 border border-muted rounded-lg cursor-pointer hover:bg-muted-5 transition-colors duration-200 hover:border-primary"
                                     wire:click="addUser({{ $user->id }})"
                                 >
                                     <div class="d-flex items-center gap-4">
                                         <div class="w-12 h-12 bg-primary rounded-full d-flex items-center justify-center text-on-primary text-lg font-semibold">
                                             {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                         </div>
                                         <div class="flex-grow-1">
                                             <h3 class="text-lg font-semibold text-secondary">{{ $user->fullname }}</h3>
                                             <p class="text-sm text-muted">{{ $user->email }}</p>
                                         </div>
                                         <div class="text-muted">
                                             @svg('heroicon-o-plus', 'w-5 h-5')
                                         </div>
                                     </div>
                                 </div>
                             @endforeach
                         </div>
                     @else
                         <div class="text-center py-8">
                             <div class="w-16 h-16 bg-muted-10 rounded-full d-flex items-center justify-center mx-auto mb-4">
                                 @svg('heroicon-o-users', 'w-8 h-8 text-muted')
                             </div>
                             <h4 class="text-lg font-semibold text-secondary mb-2">Keine verfügbaren Benutzer</h4>
                             <p class="text-muted">Alle Team-Mitglieder haben bereits Zugriff auf dieses Konto.</p>
                         </div>
                     @endif
                 </div>
             </div>

             <x-slot name="footer">
                 <div class="d-flex justify-end gap-2">
                     <x-ui-button variant="muted" wire:click="$set('showUserModal', false)">Schließen</x-ui-button>
                 </div>
             </x-slot>
        </x-ui-modal>
    </div>
</div>