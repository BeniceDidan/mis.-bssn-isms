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
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 "
        wire:click.self="close"
    >
        @if ($record)
            @php
                $levels = $record->checks['levels'] ?? [];
                $pengesahan = $record->checks['pengesahan'] ?? [];
                $pdf = $record->checks['pdf'] ?? [];
                $unreadable = $record->pdfUnreadable();
                $unreadableText = collect($record->warnings ?? [])->firstWhere('type', 'danger')['text'] ?? 'PDF tidak dapat dibaca.';
                $missing = collect($levels)
                    ->filter(fn ($level, $no) => $no <= $record->target_level)
                    ->flatMap(fn ($level) => collect($level['unsur'])->where('found', false)->pluck('label'))
                    ->unique()
                    ->values();
                $shortLabel = ['nomor' => 'Nomor', 'tanggal' => 'Tanggal', 'penandatangan' => 'NIP', 'tte' => 'Keterangan TTE'];
                // Urutan tetap; jsonb di Postgres mengurutkan ulang kunci.
                $validity = collect($shortLabel)
                    ->filter(fn ($label, $key) => isset($pengesahan[$key]))
                    ->map(fn ($label, $key) => ['label' => $label, 'found' => $pengesahan[$key]['found']])
                    ->values()
                    ->push(['label' => 'TTE tertanam', 'found' => $pdf['digital_signature'] ?? false]);
                $breakdown = $record->scoreBreakdown();
                $ai = $record->aiHighlights();
                $meets = ! $unreadable && $record->meetsTarget();
            @endphp
            <div
                x-show="show"
                x-transition:enter="transition ease-spring duration-400"
                x-transition:enter-start="opacity-0 scale-90 translate-y-6"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="glass-panel mt-12 max-h-[82vh] w-full max-w-2xl overflow-y-auto rounded-2xl shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4 px-6 pt-5">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-gray-900">{{ $record->title }}</h2>
                        <p class="text-sm text-gray-500">{{ $record->moduleLabel() }}@if ($record->unit_name) &middot; {{ $record->unit_name }}@endif</p>
                    </div>
                    <button type="button" wire:click="close" class="shrink-0 rounded-full p-1.5 text-gray-400 hover:bg-slate-50 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    <dl class="grid grid-cols-3 gap-2" data-tour="evidence-summary">
                        <div class="rounded-2xl bg-slate-50 px-3 py-3">
                            <dt class="text-xs text-gray-500">Klaim</dt>
                            <dd class="mt-0.5 text-lg font-semibold text-gray-900">Level {{ $record->target_level }}</dd>
                            <dd class="text-xs text-gray-500">{{ $record::levelName($record->target_level) }}</dd>
                        </div>
                        <div class="rounded-2xl px-3 py-3 {{ $meets ? 'bg-brand-50' : 'bg-slate-50' }}">
                            <dt class="text-xs text-gray-500">Nilai</dt>
                            <dd class="mt-0.5 text-lg font-semibold {{ $meets ? 'text-brand-700' : 'text-gray-900' }}">{{ $record->valueLabel() }}</dd>
                            @if ($record->valueLabel() !== '—')
                                <dd class="text-xs text-gray-500">{{ $record::levelName($record->achieved_level) }}</dd>
                            @endif
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-3 py-3">
                            <dt class="text-xs text-gray-500">Unsur</dt>
                            <dd class="mt-0.5 text-lg font-semibold text-gray-900">{{ $record->unsurLabel() }}</dd>
                            @if ($record->unsurLabel() !== '—')
                                <dd class="text-xs text-gray-500">ditemukan</dd>
                            @endif
                        </div>
                    </dl>

                    @if ($unreadable)
                        <p class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $unreadableText }}</p>
                    @elseif ($missing->isNotEmpty())
                        <div data-tour="evidence-missing">
                            <p class="text-sm font-medium text-gray-900">Belum ada</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($missing as $label)
                                    <span class="rounded-full bg-red-50 px-3 py-1 text-sm text-red-700">{{ $label }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($pengesahan)
                        <div data-tour="evidence-validity">
                            <p class="text-sm font-medium text-gray-900">Keabsahan</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($validity as $item)
                                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm {{ $item['found'] ? 'bg-brand-50 text-brand-700' : 'bg-slate-50 text-gray-400' }}">
                                        @if ($item['found'])
                                            <x-heroicon-o-check class="h-3.5 w-3.5" />
                                        @else
                                            <x-heroicon-o-minus class="h-3.5 w-3.5" />
                                        @endif
                                        {{ $item['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (($levels && ! $unreadable) || $ai['summary'] || $ai['gaps'])
                    <div class="space-y-4" data-tour="evidence-details">
                        @if ($ai['summary'] || $ai['gaps'])
                            <div data-tour="evidence-ai">
                                <div class="max-w-[92%] rounded-2xl rounded-tl-md bg-slate-100 px-4 py-3 text-sm leading-relaxed text-gray-700">
                                    @if ($ai['summary'])
                                        <p>{{ $ai['summary'] }}</p>
                                    @endif
                                    @if ($ai['gaps'])
                                        <ul class="{{ $ai['summary'] ? 'mt-2' : '' }} space-y-1">
                                            @foreach ($ai['gaps'] as $gap)
                                                <li class="flex gap-2"><span class="text-gray-400">–</span><span>{{ $gap }}</span></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                <p class="mt-1 text-[11px] text-gray-400">Ringkasan AI</p>
                            </div>
                        @endif

                        @if ($levels && ! $unreadable)
                            <details class="group">
                                <summary class="flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-brand-700 [&::-webkit-details-marker]:hidden">
                                    <x-heroicon-o-chevron-right class="h-4 w-4 transition group-open:rotate-90" />
                                    Asal nilai
                                </summary>
                                <div class="mt-3 space-y-2">
                                    <p class="text-xs text-gray-500">Nilai = level tertinggi yang semua unsurnya, dari Level 1, ditemukan di PDF. {{ $breakdown['found'] }} dari {{ $breakdown['total'] }} unsur Level 1–{{ $record->target_level }} ditemukan.</p>
                                    @foreach ($levels as $no => $level)
                                        @continue($no > max($record->target_level, $record->achieved_level))
                                        @php $lack = collect($level['unsur'])->where('found', false)->count(); @endphp
                                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-gray-900">Level {{ $no }} &middot; {{ $level['name'] }}</p>
                                                    @if (! empty($level['kriteria']))
                                                        <p class="text-xs text-gray-500">{{ $level['kriteria'] }}</p>
                                                    @endif
                                                </div>
                                                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $lack ? 'bg-red-50 text-red-700' : 'bg-brand-50 text-brand-700' }}">{{ $lack ? 'Kurang ' . $lack : 'Lengkap' }}</span>
                                            </div>
                                            <ul class="mt-2 space-y-1.5">
                                                @foreach ($level['unsur'] as $unsur)
                                                    <li class="flex items-start gap-2 text-sm text-gray-700">
                                                        @if ($unsur['found'])
                                                            <x-heroicon-o-check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                                                        @else
                                                            <x-heroicon-o-x-mark class="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
                                                        @endif
                                                        <span class="min-w-0">
                                                            {{ $unsur['label'] }}
                                                            @if ($unsur['found'])
                                                                <span class="block text-xs text-gray-400">“{{ $unsur['keyword'] }}”@if ($unsur['page']) · hal. {{ $unsur['page'] }}@endif</span>
                                                            @elseif ($keywords = $record->keywordsFor($no, $unsur['label']))
                                                                <span class="block text-xs text-gray-400">dicari: {{ implode(', ', array_slice($keywords, 0, 3)) }}</span>
                                                            @endif
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                    @endif

                    <div class="border-t border-slate-200 pt-4" data-tour="evidence-decision">
                        <p class="mb-2 text-sm font-medium text-gray-900">Keputusan</p>
                        @if ($canDecide)
                            <form wire:submit="saveDecision" class="space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    @foreach (\App\Models\EvidenceDocument::DECISIONS as $value => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model="decision" value="{{ $value }}" class="peer sr-only">
                                            <span class="inline-block rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm text-gray-700 transition peer-checked:border-brand-500 peer-checked:bg-brand-500 peer-checked:text-white">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('decision') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                <textarea wire:model="note" rows="2" placeholder="Catatan untuk OPD" class="w-full rounded-lg border-slate-200 bg-white text-sm focus:border-brand-400 focus:ring-brand-400"></textarea>
                                <button type="submit" class="rounded-full bg-brand-700 px-4 py-2 text-sm font-medium text-white">Simpan</button>
                            </form>
                        @elseif ($record->reviewer_decision)
                            <p class="text-sm text-gray-700">
                                {{ \App\Models\EvidenceDocument::DECISIONS[$record->reviewer_decision] }}
                                <span class="text-gray-400">· {{ $record->reviewer?->name }}, {{ optional($record->reviewed_at)->format('d M Y') }}</span>
                            </p>
                            @if ($record->reviewer_note) <p class="mt-1 text-sm text-gray-600">{{ $record->reviewer_note }}</p> @endif
                        @else
                            <p class="text-sm text-gray-500">Menunggu admin {{ $record->moduleLabel() }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4" data-tour="evidence-actions">
                    <a href="{{ route('evidence.file', $record) }}" target="_blank" class="mr-auto inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:underline">
                        <x-heroicon-o-document-text class="h-4 w-4" /> Buka PDF
                    </a>
                    @if ($canArchive)
                        <button type="button" wire:click="archive" wire:confirm="Arsipkan bukti dukung ini?" class="rounded-full px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Arsipkan</button>
                    @endif
                    @if (auth()->user()?->canWrite())
                        <button type="button" wire:click="reassess" wire:loading.attr="disabled" wire:target="reassess" class="glass-panel rounded-full px-4 py-2 text-sm font-medium text-gray-700 hover:bg-slate-50 disabled:opacity-60">
                            <span wire:loading.remove wire:target="reassess">Nilai ulang</span>
                            <span wire:loading wire:target="reassess">Menilai...</span>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
