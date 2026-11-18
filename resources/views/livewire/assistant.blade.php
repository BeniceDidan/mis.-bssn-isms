<div
    x-data="{
        open: false,
        pending: '',
        draft: '',
        init() {
            try { this.open = localStorage.getItem('bssn_assistant_open') === '1' } catch (e) {}
            this.$nextTick(() => this.scroll());
        },
        toggle(value) {
            this.open = value;
            try { localStorage.setItem('bssn_assistant_open', value ? '1' : '0') } catch (e) {}
            if (value) this.$nextTick(() => { this.scroll(); this.$refs.input && this.$refs.input.focus() });
        },
        scroll() {
            const list = this.$refs.list;
            if (list) list.scrollTop = list.scrollHeight;
        },
        grow() {
            const el = this.$refs.input;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 128) + 'px';
        },
        ask(text) {
            text = (text ?? this.draft).trim();
            if (text.length < 2 || this.pending) return;
            this.pending = text;
            this.draft = '';
            this.$nextTick(() => { this.grow(); this.scroll() });
            $wire.send(text).finally(() => {
                this.pending = '';
                this.$nextTick(() => this.scroll());
            });
        },
    }"
    @keydown.escape.window="open && toggle(false)"
    @open-assistant.window="toggle(true)"
    class="print:hidden"
>
    <button
        type="button"
        x-show="! open"
        @click="toggle(true)"
        data-tour="assistant-button"
        class="assistant-launcher fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 rounded-full bg-brand-700 py-3 pl-3.5 pr-4 text-sm font-medium text-white shadow-lg hover:bg-brand-800"
    >
        <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
        Asisten
    </button>

    <section
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        role="dialog"
        aria-label="Asisten"
        data-tour="assistant-panel"
        class="fixed inset-x-0 bottom-0 z-[60] flex h-[88vh] flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:inset-x-auto sm:bottom-5 sm:right-5 sm:h-[38rem] sm:max-h-[calc(100vh-2.5rem)] sm:w-[25rem] sm:rounded-2xl"
    >
        <header class="flex items-center justify-between border-b border-slate-200 py-2 pl-4 pr-2">
            <p class="text-sm font-semibold text-gray-900">Asisten</p>
            <div class="flex items-center">
                @if ($messages)
                    <button type="button" wire:click="clear" title="Percakapan baru" aria-label="Percakapan baru" class="rounded-full p-2 text-gray-400 hover:bg-slate-50 hover:text-gray-700">
                        <x-heroicon-o-pencil-square class="h-[18px] w-[18px]" />
                    </button>
                @endif
                <button type="button" @click="toggle(false)" title="Tutup" aria-label="Tutup" class="rounded-full p-2 text-gray-400 hover:bg-slate-50 hover:text-gray-700">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>
            </div>
        </header>

        <div x-ref="list" class="flex flex-1 flex-col gap-5 overflow-y-auto px-4 py-4" data-tour="assistant-messages">
            @if (! $messages)
                <div x-show="! pending" class="mt-auto flex flex-wrap gap-2">
                    @foreach ($examples as $example)
                        <button type="button" @click="ask(@js($example))" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-left text-sm text-gray-700 hover:border-brand-300 hover:bg-slate-50">{{ $example }}</button>
                    @endforeach
                </div>
            @endif

            @foreach ($messages as $message)
                @if ($message['role'] === 'user')
                    <div class="flex justify-end" wire:key="m-{{ $loop->index }}">
                        <p class="max-w-[85%] whitespace-pre-line break-words rounded-2xl rounded-br-md bg-brand-700 px-3.5 py-2 text-sm text-white">{{ $message['text'] }}</p>
                    </div>
                @else
                    @php $links = $message['links'] ?? []; @endphp
                    <div wire:key="m-{{ $loop->index }}" data-tour="assistant-answer" x-data="{ all: false }">
                        <p class="whitespace-pre-line break-words text-sm leading-relaxed text-gray-800" data-tour="assistant-text">{{ $message['text'] }}</p>

                        @if (! empty($message['quote']))
                            <blockquote class="mt-2 border-l-2 border-brand-200 pl-3 text-[13px] leading-relaxed text-gray-600">{{ $message['quote'] }}</blockquote>
                        @endif

                        @if (! empty($message['ai']))
                            <p class="mt-1.5 text-[11px] text-gray-400" data-tour="assistant-ai-label">Dirangkum AI dari {{ $message['ai'] }}</p>
                        @endif

                        @if (! empty($message['draft']))
                            <p class="mt-1.5 text-[11px] text-gray-400" data-tour="assistant-draft-label">Riset draf, belum ditinjau</p>
                        @endif

                        @if ($links)
                            <ul class="mt-3 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200">
                                @foreach ($links as $link)
                                    <li @if ($loop->index >= 4) x-show="all" x-cloak @endif>
                                        <a
                                            href="{{ $link['url'] }}"
                                            @if ($link['kind'] === 'file') target="_blank" rel="noopener" @endif
                                            @if ($link['kind'] === 'web') target="_blank" rel="noopener noreferrer" @endif
                                            class="flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50"
                                        >
                                            @if ($link['kind'] === 'file')
                                                <x-heroicon-o-document-text class="h-4 w-4 shrink-0 text-gray-400" />
                                            @elseif ($link['kind'] === 'web')
                                                <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4 shrink-0 text-gray-400" />
                                            @elseif ($link['kind'] === 'page')
                                                <x-heroicon-o-arrow-right class="h-4 w-4 shrink-0 text-gray-400" />
                                            @else
                                                <x-heroicon-o-table-cells class="h-4 w-4 shrink-0 text-gray-400" />
                                            @endif
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm text-gray-900">{{ $link['title'] }}</span>
                                                @if ($link['meta'])
                                                    <span class="block truncate text-xs text-gray-500">{{ $link['meta'] }}</span>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                                @if (count($links) > 4)
                                    <li x-show="! all">
                                        <button type="button" @click="all = true" class="w-full px-3 py-2 text-left text-xs font-medium text-brand-700 hover:bg-slate-50">{{ count($links) - 4 }} lainnya</button>
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </div>
                @endif
            @endforeach

            <template x-if="pending">
                <div class="space-y-5">
                    <div class="flex justify-end">
                        <p class="max-w-[85%] whitespace-pre-line break-words rounded-2xl rounded-br-md bg-brand-700 px-3.5 py-2 text-sm text-white" x-text="pending"></p>
                    </div>
                    <div class="flex gap-1 py-1" aria-label="Sedang mencari">
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:150ms]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:300ms]"></span>
                    </div>
                </div>
            </template>
        </div>

        <form @submit.prevent="ask()" class="p-3">
            <div class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-white py-1.5 pl-3.5 pr-1.5 focus-within:border-brand-400">
                <textarea
                    x-ref="input"
                    x-model="draft"
                    @input="grow()"
                    rows="1"
                    maxlength="500"
                    placeholder="Tanya tentang aplikasi ini"
                    aria-label="Pertanyaan"
                    data-tour="assistant-input"
                    @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); ask() }"
                    class="max-h-32 flex-1 resize-none border-0 bg-transparent py-1.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:ring-0"
                ></textarea>
                <button type="submit" :disabled="pending !== '' || draft.trim().length < 2" title="Kirim" aria-label="Kirim" class="rounded-full bg-brand-700 p-2 text-white hover:bg-brand-800 disabled:bg-slate-200 disabled:text-gray-400">
                    <x-heroicon-o-arrow-up class="h-4 w-4" />
                </button>
            </div>
        </form>
    </section>
</div>
