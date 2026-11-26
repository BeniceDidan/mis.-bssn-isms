@props(['feature', 'variant' => 'button'])

@php($guide = \App\Support\AiGuide::for($feature))

@if ($guide)
    <div x-data="{ open: false, tab: 'kerja' }" @keydown.escape.window="open = false" class="inline-block">
        @if ($variant === 'link')
            <button type="button" @click="open = true; tab = 'kerja'" class="text-xs font-medium text-brand-700 hover:underline">
                Cara kerjanya?
            </button>
        @else
            <button
                type="button"
                x-ripple
                @click="open = true; tab = 'kerja'"
                title="Panduan {{ $guide['title'] }}"
                class="glass-panel inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium text-gray-700 transition-all duration-300 ease-spring hover:bg-white"
            >
                <x-heroicon-o-book-open class="h-3.5 w-3.5" />
                Panduan AI
            </button>
        @endif

        <template x-teleport="body">
            <div
                x-show="open"
                x-cloak
                x-transition.opacity.duration.200ms
                class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-slate-900/40 px-4 pb-10 pt-[6vh]"
                @click.self="open = false"
            >
                <div class="glass-panel h-fit w-full max-w-2xl overflow-hidden rounded-2xl shadow-2xl" role="dialog" aria-modal="true" aria-label="Panduan {{ $guide['title'] }}">
                    <div class="flex items-start justify-between gap-4 px-6 pt-5">
                        <h2 class="min-w-0 text-lg font-semibold text-gray-900">{{ $guide['title'] }}</h2>
                        <button type="button" @click="open = false" class="shrink-0 rounded-full p-1.5 text-gray-400 hover:bg-slate-50 hover:text-gray-600" aria-label="Tutup">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="px-6 pt-4">
                        <div class="flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1">
                            @foreach (['kerja' => 'Cara kerja', 'tutorial' => 'Tutorial', 'faq' => 'Tanya jawab'] as $key => $label)
                                <button
                                    type="button"
                                    @click="tab = '{{ $key }}'"
                                    :class="tab === '{{ $key }}' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                                    class="flex-1 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-medium"
                                >{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="max-h-[64vh] overflow-y-auto px-6 py-5">
                        <div x-show="tab === 'kerja'" class="space-y-5">
                            <p class="text-sm leading-relaxed text-gray-700">{{ $guide['intro'] }}</p>
                            <ol class="space-y-2">
                                @foreach ($guide['flow'] as $step)
                                    <li class="flex gap-3 rounded-xl px-3 py-2.5 {{ $step['who'] === 'ai' ? 'bg-brand-50' : 'bg-slate-50' }}">
                                        <span class="w-14 shrink-0 pt-0.5 text-xs font-medium {{ $step['who'] === 'ai' ? 'text-brand-700' : 'text-gray-500' }}">{{ \App\Support\AiGuide::WHO[$step['who']] }}</span>
                                        <span class="text-sm leading-relaxed text-gray-700">{{ $step['text'] }}</span>
                                    </li>
                                @endforeach
                            </ol>
                            <p class="text-sm text-gray-500">Semua AI berjalan di server sendiri. Hasil AI berupa saran, dan setiap hasilnya dicatat.</p>
                        </div>

                        <div x-show="tab === 'tutorial'" x-cloak class="space-y-5">
                            <ol class="list-decimal space-y-2.5 pl-5 text-sm leading-relaxed text-gray-700 marker:text-gray-400">
                                @foreach ($guide['steps'] as $step)
                                    <li class="pl-1">{{ $step }}</li>
                                @endforeach
                            </ol>
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <p class="text-sm font-medium text-gray-900">Contoh</p>
                                <p class="mt-1 text-sm leading-relaxed text-gray-700">{{ $guide['example'] }}</p>
                            </div>
                        </div>

                        <div x-show="tab === 'faq'" x-cloak>
                            <dl class="divide-y divide-slate-200">
                                @foreach ($guide['faq'] as $item)
                                    <div class="py-3 first:pt-0 last:pb-0">
                                        <dt class="text-sm font-medium text-gray-900">{{ $item['q'] }}</dt>
                                        <dd class="mt-1 text-sm leading-relaxed text-gray-700">{{ $item['a'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-6 py-3">
                        <button type="button" @click="open = false" class="rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-slate-50">Tutup</button>
                        <button
                            type="button"
                            @click="open = false; setTimeout(() => window.startAiTour && window.startAiTour('{{ $feature }}'), 250)"
                            class="inline-flex items-center gap-1.5 rounded-full bg-brand-700 px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-800"
                        >
                            <x-heroicon-o-play class="h-4 w-4" />
                            Mulai tutorial
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
