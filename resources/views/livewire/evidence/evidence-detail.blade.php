<div x-data="{ show: @entangle('show') }">
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
        wire:click.self="close"
    >
        @if ($record)
            @php
                $levels = $record->checks['levels'] ?? [];
                $pengesahan = $record->checks['pengesahan'] ?? [];
                $pdf = $record->checks['pdf'] ?? [];
                $tone = [
                    'ok' => 'border-green-200 bg-green-50/80 text-green-800 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-200',
                    'warning' => 'border-amber-200 bg-amber-50/80 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200',
                    'danger' => 'border-red-200 bg-red-50/80 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200',
                ];
            @endphp
            <div
                x-show="show"
                x-transition:enter="transition ease-spring duration-400"
                x-transition:enter-start="opacity-0 scale-90 translate-y-6"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="glass-panel mt-12 max-h-[82vh] w-full max-w-3xl overflow-y-auto rounded-2xl shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4 border-b border-white/40 px-6 py-4">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-gray-900">{{ $record->title }}</h2>
                        <p class="text-xs text-gray-500">
                            <span class="font-mono">{{ $record->record_code }}</span>
                            &middot; {{ $record->moduleLabel() }}
                            @if ($record->unit_name) &middot; {{ $record->unit_name }} @endif
                            &middot; {{ $record->page_count }} hal.
                        </p>
                    </div>
                    <button type="button" wire:click="close" class="shrink-0 rounded-full p-1.5 text-gray-400 transition-all duration-300 ease-spring hover:rotate-90 hover:bg-white/60 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-xl bg-white/60 p-3">
                            <p class="text-xs text-gray-500">Klaim</p>
                            <p class="text-lg font-bold text-gray-900">Level {{ $record->target_level }}</p>
                            <p class="text-xs text-gray-500">{{ \App\Models\EvidenceDocument::levelName($record->target_level) }}</p>
                        </div>
                        <div class="rounded-xl bg-white/60 p-3">
                            <p class="text-xs text-gray-500">Hasil penilaian</p>
                            <p class="text-lg font-bold {{ $record->meetsTarget() ? 'text-green-600' : 'text-amber-600' }}">
                                {{ $record->achieved_level > 0 ? 'Level ' . $record->achieved_level : '—' }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $record->achieved_level > 0 ? \App\Models\EvidenceDocument::levelName($record->achieved_level) : 'Belum memenuhi' }}</p>
                        </div>
                        <div class="rounded-xl bg-white/60 p-3">
                            <p class="text-xs text-gray-500">Skor kelengkapan</p>
                            <p class="text-lg font-bold text-gray-900">{{ $record->score }}<span class="text-sm font-medium text-gray-400">/100</span></p>
                            <p class="text-xs text-gray-500">s.d. Level {{ $record->target_level }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        @foreach ($record->warnings ?? [] as $warning)
                            <div class="rounded-xl border px-3 py-2 text-sm {{ $tone[$warning['type']] ?? $tone['warning'] }}">{{ $warning['text'] }}</div>
                        @endforeach
                    </div>

                    @if ($levels)
                        <div>
                            <p class="mb-2 text-sm font-semibold text-gray-800">{{ $indicator }}</p>
                            <div class="divide-y divide-white/50 overflow-hidden rounded-xl bg-white/50">
                                @foreach ($levels as $no => $level)
                                    <div class="px-4 py-3 {{ $no > $record->target_level ? 'opacity-50' : '' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-medium text-gray-800">Level {{ $no }} · {{ $level['name'] }}</p>
                                            @if ($level['met'])
                                                <x-heroicon-s-check-circle class="h-5 w-5 text-green-500" />
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500">{{ $level['kriteria'] }}</p>
                                        <ul class="mt-1.5 space-y-1">
                                            @foreach ($level['unsur'] as $unsur)
                                                <li class="flex items-start gap-2 text-xs">
                                                    @if ($unsur['found'])
                                                        <x-heroicon-o-check class="mt-0.5 h-3.5 w-3.5 shrink-0 text-green-600" />
                                                        <span class="text-gray-700">{{ $unsur['label'] }} <span class="text-gray-400">— “{{ $unsur['keyword'] }}”, hal. {{ $unsur['page'] }}</span></span>
                                                    @else
                                                        <x-heroicon-o-x-mark class="mt-0.5 h-3.5 w-3.5 shrink-0 text-red-500" />
                                                        <span class="text-gray-700">{{ $unsur['label'] }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($pengesahan)
                        <div>
                            <p class="mb-2 text-sm font-semibold text-gray-800">Keabsahan dokumen</p>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                @foreach ($pengesahan as $item)
                                    <div class="flex items-start gap-2 rounded-lg bg-white/50 px-3 py-2">
                                        @if ($item['found'])
                                            <x-heroicon-o-check class="mt-0.5 h-3.5 w-3.5 shrink-0 text-green-600" />
                                        @else
                                            <x-heroicon-o-minus class="mt-0.5 h-3.5 w-3.5 shrink-0 text-gray-400" />
                                        @endif
                                        <span class="min-w-0 text-gray-700">{{ $item['label'] }}
                                            @if ($item['match']) <span class="block truncate text-gray-400">{{ $item['match'] }}</span> @endif
                                        </span>
                                    </div>
                                @endforeach
                                <div class="flex items-start gap-2 rounded-lg bg-white/50 px-3 py-2">
                                    @if ($pdf['digital_signature'] ?? false)
                                        <x-heroicon-o-check class="mt-0.5 h-3.5 w-3.5 shrink-0 text-green-600" />
                                    @else
                                        <x-heroicon-o-minus class="mt-0.5 h-3.5 w-3.5 shrink-0 text-gray-400" />
                                    @endif
                                    <span class="text-gray-700">Tanda tangan digital tertanam</span>
                                </div>
                                <div class="flex items-start gap-2 rounded-lg bg-white/50 px-3 py-2">
                                    <x-heroicon-o-photo class="mt-0.5 h-3.5 w-3.5 shrink-0 text-gray-400" />
                                    <span class="text-gray-700">Gambar di halaman terakhir: {{ $pdf['images_last_page'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($record->ai_notes)
                        <div class="rounded-xl bg-white/60 px-4 py-3">
                            <p class="mb-1 text-sm font-semibold text-gray-800">Catatan AI</p>
                            <p class="whitespace-pre-line text-sm text-gray-700">{{ $record->ai_notes }}</p>
                        </div>
                    @endif

                    <p class="text-xs text-gray-400">
                        Dinilai {{ optional($record->assessed_at)->format('d M Y H:i') }} oleh {{ $record->engine }}.
                        Hasil otomatis ini draf; keputusan ada pada asesor.
                    </p>

                    <div class="border-t border-white/40 pt-4">
                        <p class="mb-2 text-sm font-semibold text-gray-800">Keputusan asesor</p>
                        @if ($canDecide)
                            <form wire:submit="saveDecision" class="space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    @foreach (\App\Models\EvidenceDocument::DECISIONS as $value => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model="decision" value="{{ $value }}" class="peer sr-only">
                                            <span class="inline-block rounded-full border border-white/60 bg-white/50 px-3 py-1.5 text-sm text-gray-700 transition peer-checked:border-sky-500 peer-checked:bg-sky-500 peer-checked:text-white">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('decision') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                <textarea wire:model="note" rows="2" placeholder="Catatan untuk OPD (opsional)" class="w-full rounded-lg border-white/60 bg-white/50 text-sm focus:border-sky-400 focus:ring-sky-400"></textarea>
                                <button type="submit" class="rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30">Simpan keputusan</button>
                            </form>
                        @elseif ($record->reviewer_decision)
                            <p class="text-sm text-gray-700">
                                {{ \App\Models\EvidenceDocument::DECISIONS[$record->reviewer_decision] }}
                                <span class="text-gray-400">— {{ $record->reviewer?->name }}, {{ optional($record->reviewed_at)->format('d M Y') }}</span>
                            </p>
                            @if ($record->reviewer_note) <p class="mt-1 text-sm text-gray-600">{{ $record->reviewer_note }}</p> @endif
                        @else
                            <p class="text-sm text-gray-500">Menunggu admin {{ $record->moduleLabel() }}.</p>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-white/40 px-6 py-4">
                    <a href="{{ route('evidence.file', $record) }}" target="_blank" class="mr-auto inline-flex items-center gap-1.5 text-sm font-medium text-sky-700 hover:underline">
                        <x-heroicon-o-document-text class="h-4 w-4" /> {{ \Illuminate\Support\Str::limit($record->original_name, 40) }}
                    </a>
                    @if ($canArchive)
                        <button type="button" wire:click="archive" wire:confirm="Arsipkan bukti dukung ini?" class="rounded-full px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Arsipkan</button>
                    @endif
                    @if (auth()->user()?->canWrite())
                        <button type="button" wire:click="reassess" wire:loading.attr="disabled" wire:target="reassess" class="glass-panel rounded-full px-4 py-2 text-sm font-medium text-gray-700 hover:bg-white/80 disabled:opacity-60">
                            <span wire:loading.remove wire:target="reassess">Nilai ulang</span>
                            <span wire:loading wire:target="reassess">Menilai...</span>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
