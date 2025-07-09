<div class="flex items-start">
    <span class="w-28 pt-2 text-sm text-slate-500">Aktivitäten:</span>

    <div class="flex-1">

        {{-- Eingabefeld --}}
        <div class="flex gap-2 mb-4">
            <textarea
                wire:model="message"
                rows="1"
                placeholder="Nachricht schreiben …"
                class="flex-1 resize-none rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm shadow-inner
                       focus:bg-white focus:ring-2 focus:ring-purple-500 transition"
            ></textarea>

            <button
                wire:click="save"
                class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 transition"
            >
                Senden
            </button>
        </div>


        {{-- Aktivitäten-Liste --}}
        <ul class="space-y-4">
            @forelse ($activities as $activity)
                <li class="rounded-lg border border-slate-200/60 bg-white shadow-sm hover:border-purple-200/60 transition"
                    wire:key="activity-id-{{ $activity->id }}">

                    {{-- Kopfzeile mit Avatar & Meta --}}
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img class="h-8 w-8 rounded-full ring-1 ring-white"
                                 src="{{ $activity->user?->profile_photo_url }}"
                                 alt="{{ $activity->user?->name }}">

                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $activity->user?->name }}</p>
                                <p class="text-xs text-slate-500">{{ $activity->user?->email }}</p>
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-sm text-purple-600">{{ ucfirst($activity->activity_type) }}</p>
                            <p class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    {{-- Nachricht (falls vorhanden) --}}
                    @if($activity->message)
                        <div class="px-6 py-4 text-sm leading-relaxed text-slate-600">
                            “{{ $activity->message }}”
                        </div>
                    @endif

                    {{-- Änderungen (old / new) --}}
                    @php $props = json_decode($activity->properties, true); @endphp
                    @if(!empty($props['old']) || !empty($props['new']))
                        <div class="px-6 pb-4">
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="grid gap-6 md:grid-cols-2">
                                    <div>
                                        <h4 class="mb-2 text-xs font-semibold text-slate-700">Vorher</h4>
                                        <ul class="space-y-0.5 text-xs text-slate-600">
                                            @forelse($props['old'] as $k => $v)
                                                <li><span class="font-medium">{{ ucfirst($k) }}:</span> {{ $v }}</li>
                                            @empty
                                                <li class="italic text-slate-400">–</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                    <div>
                                        <h4 class="mb-2 text-xs font-semibold text-slate-700">Neu</h4>
                                        <ul class="space-y-0.5 text-xs text-slate-600">
                                            @forelse($props['new'] as $k => $v)
                                                <li><span class="font-medium">{{ ucfirst($k) }}:</span> {{ $v }}</li>
                                            @empty
                                                <li class="italic text-slate-400">–</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </li>

            @empty
                <li class="py-12 text-center">
                    <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    <h3 class="mt-3 text-sm font-medium text-slate-900">Keine Aktivitäten</h3>
                    <p class="mt-1 text-sm text-slate-500">Starte eine Konversation oder führe eine Änderung durch.</p>
                </li>
            @endforelse
        </ul>
    </div>
</div>