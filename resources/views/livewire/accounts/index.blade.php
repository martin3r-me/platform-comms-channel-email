<div class="h-full d-flex flex-col">
    {{-- Channel-Navigation mit Tabs --}}
    <div class="border-bottom-1 border-bottom-solid border-bottom-muted bg-muted-5 flex-shrink-0">
        <div class="d-flex items-center justify-between px-4 py-3">
            <div class="d-flex items-center gap-3">
                <div class="text-lg font-semibold text-secondary">
                    {{ $account->label ?? $account->address ?? $account->email }}
                </div>
                @if (!empty($context))
                    <x-ui-badge variant="info" size="sm">{{ class_basename($context['model'] ?? '') }} #{{ $context['modelId'] ?? '' }}</x-ui-badge>
                @endif
            </div>
            <div class="d-flex items-center gap-2">
                <x-ui-button variant="primary" size="sm" wire:click="startNewMessage" wire:key="btn-start-new">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Neue Nachricht</span>
                    </div>
                </x-ui-button>
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

    {{-- Comms-Mode: nur Threads/Reply (MVP) --}}
    @if(($ui_mode ?? 'comms') === 'comms')
    <div>
        {{-- Nachrichten-Tab --}}
        <div class="flex-grow-1 d-flex gap-0 overflow-hidden">
            {{-- Linke Spalte: Thread-Liste --}}
            <div class="w-96 flex-shrink-0 overflow-y-auto border-right-1 border-right-solid border-right-muted {{ ($activeThread || $composeMode) ? 'hidden md:block' : '' }}">
                <div class="p-4">
                    <h3 class="text-sm text-muted-foreground font-semibold uppercase mb-4">Nachrichten</h3>
                    
                    @if($this->threads->count() > 0)
                        <div class="d-flex flex-col gap-2">
                            @foreach ($this->threads as $thread)
                                @php 
                                    $threadKey = "thread-{$thread->id}";
                                    $latestMessage = $thread->timeline()->last();
                                    $isActive = $activeThread && $activeThread->id === $thread->id;
                                    $previewSource = $latestMessage?->text_body ?: strip_tags($latestMessage?->html_body ?? '');
                                    $preview = \Illuminate\Support\Str::limit(trim((string) $previewSource), 120);
                                @endphp

                                <div
                                    class="p-3 rounded-lg cursor-pointer transition-all duration-200 border-l-4 {{ $isActive ? 'border-primary bg-primary-5 border border-primary' : 'border-transparent border border-muted hover:border-primary hover:bg-primary-5' }}"
                                    wire:click="selectThread({{ $thread->id }}, {{ $latestMessage?->id }}, '{{ $latestMessage?->direction }}')"
                                    wire:key="{{ $threadKey }}"
                                >
                                    <div class="d-flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="font-semibold text-secondary truncate">
                                                {{ $thread->subject ?: 'Kein Betreff' }}
                                            </div>
                                            <div class="text-sm text-muted truncate mt-1">
                                                {{ $preview ?: 'Keine Vorschau verfügbar' }}
                                            </div>
                                            @if($latestMessage)
                                                <div class="d-flex items-center gap-2 mt-2 text-xs text-muted">
                                                    <x-ui-badge 
                                                        variant="{{ $latestMessage->direction === 'inbound' ? 'primary' : 'secondary' }}" 
                                                        size="xs"
                                                    >
                                                        {{ $latestMessage->direction === 'inbound' ? 'Eingehend' : 'Ausgehend' }}
                                                    </x-ui-badge>
                                                    <span class="truncate">
                                                        {{ $latestMessage->direction === 'inbound' ? ('Von: ' . ($latestMessage->from ?? '')) : ('An: ' . ($latestMessage->to ?? '')) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-shrink-0 text-xs text-muted whitespace-nowrap">
                                            {{ $latestMessage ? \Carbon\Carbon::parse($latestMessage->occurred_at)->format('d.m. H:i') : '' }}
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
                        <div class="d-flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold m-0">Neue Nachricht</h3>
                            <x-ui-button size="sm" variant="secondary-outline" wire:click="backToThreadList">Zurück</x-ui-button>
                        </div>
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
                    {{-- Conversation-Viewer: kompakt mit Stats --}}
                    <div class="d-flex flex-col flex-grow-1 overflow-hidden bg-white">
                        @php 
                            $messages = $activeThread->timeline()->sortBy('occurred_at');
                            $count = $messages->count();
                            $first = $messages->first();
                            $lastMsg = $messages->last();
                        @endphp
                        {{-- Header: Thread-Infos --}}
                        <div class="border-bottom-1 border-bottom-solid border-bottom-muted p-4 flex-shrink-0">
                            <div class="d-flex justify-between items-start gap-3">
                                <div class="flex-grow-1 space-y-1">
                                    <h2 class="text-lg font-semibold text-secondary mb-0">
                                        {{ $activeThread->subject ?? 'Kein Betreff' }}
                                    </h2>
                                    <div class="text-xs text-muted">
                                        {{ $count }} Nachrichten · Start: {{ $first ? \Carbon\Carbon::parse($first->occurred_at)->format('d.m.Y H:i') : '–' }} · Letzte: {{ $lastMsg ? \Carbon\Carbon::parse($lastMsg->occurred_at)->format('d.m.Y H:i') : '–' }}
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <x-ui-button size="sm" variant="secondary-outline" wire:click="backToThreadList">Zurück</x-ui-button>
                                </div>
                            </div>
                        </div>

                        {{-- Verlauf (Chat-like) --}}
                        <div class="flex-grow-1 overflow-y-auto p-6 space-y-4">
                            @forelse($messages as $message)
                                @php $isInbound = $message->direction === 'inbound'; @endphp
                                <div class="d-flex flex-col {{ $isInbound ? '' : 'items-end' }}">
                                    <div class="w-full max-w-3xl {{ $isInbound ? '' : 'ms-auto' }}">
                                        <div class="rounded-xl border {{ $isInbound ? 'border-primary bg-primary-5' : 'border-muted bg-white' }} p-4">
                                            <div class="d-flex items-center justify-between text-xs text-muted mb-2">
                                                <div class="d-flex items-center gap-2 min-w-0">
                                                    @if ($isInbound)
                                                        @svg('heroicon-o-arrow-down', 'w-4 h-4 text-primary')
                                                        <span class="truncate">Von: {{ $message->from }}</span>
                                                    @else
                                                        @svg('heroicon-o-arrow-up', 'w-4 h-4 text-secondary')
                                                        <span class="truncate">An: {{ $message->to }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex-shrink-0 d-flex items-center gap-2">
                                                    <x-ui-badge variant="{{ $isInbound ? 'primary' : 'secondary' }}" size="xs">
                                                        {{ $isInbound ? 'Eingehend' : 'Ausgehend' }}
                                                    </x-ui-badge>
                                                    <span>{{ \Carbon\Carbon::parse($message->occurred_at)->format('d.m.Y H:i') }}</span>
                                                </div>
                                            </div>
                                            <div class="prose prose-sm max-w-none text-sm leading-relaxed">
                                                {!! $message->html_body ?: nl2br(e($message->text_body)) !!}
                                            </div>
                                        </div>
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
    </div>
    @endif
</div>