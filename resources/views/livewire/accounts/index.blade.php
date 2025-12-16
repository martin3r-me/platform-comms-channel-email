<div class="h-full flex flex-col bg-white">
    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200">
        <div class="min-w-0">
            <div class="text-base font-semibold text-gray-900 truncate">
                {{ $account->label ?? $account->address ?? $account->email }}
            </div>
            @if (!empty($context))
                <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 font-medium text-blue-700">
                        {{ class_basename($context['model'] ?? '') }} #{{ $context['modelId'] ?? '' }}
                    </span>
                    @if (!empty($context['url']))
                        <a href="{{ $context['url'] }}" target="_blank" class="hover:text-gray-700 underline-offset-2 hover:underline">
                            Kontext öffnen
                        </a>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if (!empty($context))
                <button
                    type="button"
                    wire:click="$toggle('showContextDetails')"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    @svg('heroicon-o-information-circle', 'w-4 h-4')
                    <span class="hidden sm:inline">{{ $showContextDetails ? 'Kontext ausblenden' : 'Kontext' }}</span>
                </button>
            @endif

            <button
                type="button"
                wire:click="startNewMessage"
                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue Nachricht</span>
            </button>
        </div>
    </div>

    {{-- Kontextdetails (optional) --}}
    @if ($showContextDetails && !empty($context))
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-700">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div><span class="font-semibold">Typ:</span> {{ class_basename($context['model'] ?? '') }}</div>
                <div><span class="font-semibold">ID:</span> {{ $context['modelId'] ?? '' }}</div>
                @if (!empty($context['subject']))
                    <div class="sm:col-span-2"><span class="font-semibold">Betreff:</span> {{ $context['subject'] }}</div>
                @endif
                @if (!empty($context['description']))
                    <div class="sm:col-span-2">
                        <span class="font-semibold">Beschreibung:</span>
                        <div class="mt-1 whitespace-pre-wrap text-gray-600">{{ $context['description'] }}</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Comms-Mode: nur Threads/Reply (MVP) --}}
    @if(($ui_mode ?? 'comms') === 'comms')
        <div class="flex-1 min-h-0 flex divide-x divide-gray-200">
            {{-- Thread-Liste --}}
            <div class="w-80 lg:w-96 shrink-0 min-h-0 overflow-y-auto {{ ($activeThread || $composeMode) ? 'hidden md:block' : '' }}">
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Threads</div>
                </div>

                <div class="p-2">
                    @if($this->threads->count() > 0)
                        <div class="space-y-1">
                            @foreach ($this->threads as $thread)
                                @php
                                    $threadKey = "thread-{$thread->id}";
                                    $latestMessage = $thread->timeline()->last();
                                    $isActive = $activeThread && $activeThread->id === $thread->id;
                                    $previewSource = $latestMessage?->text_body ?: strip_tags($latestMessage?->html_body ?? '');
                                    $preview = \Illuminate\Support\Str::limit(trim((string) $previewSource), 120);
                                    $time = $latestMessage ? \Carbon\Carbon::parse($latestMessage->occurred_at)->format('d.m. H:i') : null;
                                    $dir = $latestMessage?->direction ?? null;
                                @endphp

                                <button
                                    type="button"
                                    class="w-full text-left rounded-lg border px-3 py-3 transition
                                    {{ $isActive ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50' }}"
                                    wire:click="selectThread({{ $thread->id }}, {{ $latestMessage?->id }}, '{{ $latestMessage?->direction }}')"
                                    wire:key="{{ $threadKey }}"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="truncate text-sm font-semibold text-gray-900">
                                                    {{ $thread->subject ?: 'Kein Betreff' }}
                                                </span>
                                                @if($dir)
                                                    <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $dir === 'inbound' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                                        {{ $dir === 'inbound' ? 'Inbound' : 'Outbound' }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-1 truncate text-sm text-gray-500">
                                                {{ $preview ?: 'Keine Vorschau verfügbar' }}
                                            </div>
                                            @if($latestMessage)
                                                <div class="mt-2 truncate text-xs text-gray-500">
                                                    {{ $latestMessage->direction === 'inbound'
                                                        ? ('Von: ' . ($latestMessage->from ?? ''))
                                                        : ('An: ' . ($latestMessage->to ?? '')) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="shrink-0 text-xs text-gray-400 whitespace-nowrap">
                                            {{ $time }}
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                @svg('heroicon-o-envelope', 'w-6 h-6 text-gray-500')
                            </div>
                            <div class="text-sm font-semibold text-gray-900">Keine Threads</div>
                            <div class="mt-1 text-sm text-gray-500">In diesem Kontext gibt es noch keine Kommunikation.</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Detail / Composer --}}
            <div class="flex-1 min-h-0 flex flex-col">
                @if ($composeMode)
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                        <div class="text-sm font-semibold text-gray-900">Neue Nachricht</div>
                        <button type="button" wire:click="backToThreadList" class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            Zurück
                        </button>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto p-4">
                        <div class="max-w-3xl space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Empfänger</label>
                                <input
                                    type="email"
                                    wire:model.defer="compose.to"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="name@firma.de"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Betreff</label>
                                <input
                                    type="text"
                                    wire:model.defer="compose.subject"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Betreff"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nachricht</label>
                                <textarea
                                    rows="12"
                                    wire:model.defer="compose.body"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Deine Nachricht…"
                                ></textarea>
                            </div>

                            @if ($showContextDetails && $this->contextBlockHtml)
                                <div class="rounded-md border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700">
                                    <div class="font-semibold mb-2">Kontext wird angehängt</div>
                                    {!! $this->contextBlockHtml !!}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-gray-200 bg-white px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="$set('composeMode', false)" class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Abbrechen
                            </button>
                            <button type="button" wire:click="sendNewMessage" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Senden
                            </button>
                        </div>
                    </div>
                @elseif ($activeThread)
                    @php
                        $messages = $activeThread->timeline()->sortBy('occurred_at');
                        $count = $messages->count();
                        $first = $messages->first();
                        $lastMsg = $messages->last();
                    @endphp

                    <div class="flex items-start justify-between gap-3 px-4 py-3 border-b border-gray-200">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-gray-900 truncate">
                                {{ $activeThread->subject ?? 'Kein Betreff' }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ $count }} Nachrichten · Start: {{ $first ? \Carbon\Carbon::parse($first->occurred_at)->format('d.m.Y H:i') : '–' }} · Letzte: {{ $lastMsg ? \Carbon\Carbon::parse($lastMsg->occurred_at)->format('d.m.Y H:i') : '–' }}
                            </div>
                        </div>
                        <button type="button" wire:click="backToThreadList" class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span class="hidden sm:inline">Zurück</span>
                        </button>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto bg-gray-50 px-4 py-6 space-y-4">
                        @forelse($messages as $message)
                            @php $isInbound = $message->direction === 'inbound'; @endphp
                            <div class="flex {{ $isInbound ? 'justify-start' : 'justify-end' }}">
                                <div class="w-full max-w-3xl">
                                    <div class="rounded-2xl border px-4 py-3 {{ $isInbound ? 'bg-white border-gray-200' : 'bg-blue-600 border-blue-700 text-white' }}">
                                        <div class="flex items-center justify-between gap-3 text-xs {{ $isInbound ? 'text-gray-500' : 'text-blue-100' }}">
                                            <div class="truncate">
                                                {{ $isInbound ? ('Von: ' . ($message->from ?? '')) : ('An: ' . ($message->to ?? '')) }}
                                            </div>
                                            <div class="shrink-0 whitespace-nowrap">
                                                {{ \Carbon\Carbon::parse($message->occurred_at)->format('d.m.Y H:i') }}
                                            </div>
                                        </div>
                                        <div class="mt-2 prose prose-sm max-w-none {{ $isInbound ? 'text-gray-900' : 'prose-invert text-white' }}">
                                            {!! $message->html_body ?: nl2br(e($message->text_body)) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-sm text-gray-500">Keine Nachrichten im Thread.</div>
                        @endforelse
                    </div>

                    <div class="border-t border-gray-200 bg-white px-4 py-3">
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Antwort</label>
                                <textarea
                                    rows="3"
                                    wire:model.defer="replyBody"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Kurze Antwort… (Verlauf wird automatisch angehängt)"
                                ></textarea>
                            </div>
                            <button type="button" wire:click="sendReply" class="shrink-0 rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                                Senden
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex-1 min-h-0 flex items-center justify-center bg-gray-50">
                        <div class="text-center px-6">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-white border border-gray-200">
                                @svg('heroicon-o-envelope', 'w-7 h-7 text-gray-600')
                            </div>
                            <div class="text-sm font-semibold text-gray-900">Thread auswählen</div>
                            <div class="mt-1 text-sm text-gray-500">Links einen Thread auswählen oder eine neue Nachricht starten.</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="flex-1 min-h-0 flex items-center justify-center bg-gray-50">
            <div class="text-center px-6">
                <div class="text-sm font-semibold text-gray-900">Dieses Konto wird hier nicht verwaltet.</div>
                <div class="mt-1 text-sm text-gray-500">Bitte nutze „Kanäle verwalten“ im Comms-Modal (Board/Project).</div>
            </div>
        </div>
    @endif
</div>