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
        ask(text) {
            text = (text ?? this.draft).trim();
            if (text.length < 2 || this.pending) return;
            this.pending = text;
            this.draft = '';
            this.$nextTick(() => this.scroll());
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
        class="assistant-launcher fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 rounded-full bg-brand-700 px-4 py-3 text-sm font-medium text-white shadow-lg hover:bg-brand-800"
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
        class="fixed inset-x-0 bottom-0 z-[60] flex h-[85vh] flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:inset-x-auto sm:bottom-5 sm:right-5 sm:h-[36rem] sm:max-h-[calc(100vh-2.5rem)] sm:w-[26rem] sm:rounded-2xl"
    >
        <header class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
            <p class="text-sm font-semibold text-gray-900">Asisten BSSN ISMS</p>
            <div class="flex items-center gap-1">
                @if ($messages)
                    <button type="button" wire:click="clear" title="Mulai percakapan baru" class="rounded-full p-1.5 text-gray-400 hover:bg-slate-50 hover:text-gray-600">
                        <x-heroicon-o-arrow-path class="h-4 w-4" />
                    </button>
                @endif
                <button type="button" @click="toggle(false)" title="Tutup" class="rounded-full p-1.5 text-gray-400 hover:bg-slate-50 hover:text-gray-600">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>
            </div>
        </header>

        <div x-ref="list" class="flex-1 space-y-4 overflow-y-auto px-4 py-4" data-tour="assistant-messages">
            @if (! $messages)
                <div x-show="! pending">
                    <p class="text-sm text-gray-600">Tanyakan cara memakai aplikasi, cari data atau berkas, atau tanyakan isi dokumen.</p>
                    <div class="mt-3 flex flex-col items-start gap-2">
                        @foreach ($examples as $example)
                            <button type="button" @click="ask(@js($example))" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-slate-50">{{ $example }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($messages as $message)
                @if ($message['role'] === 'user')
                    <div class="flex justify-end" wire:key="m-{{ $loop->index }}">
                        <p class="max-w-[85%] whitespace-pre-line break-words rounded-2xl rounded-br-md bg-brand-700 px-3 py-2 text-sm text-white">{{ $message['text'] }}</p>
                    </div>
                @else
                    <div class="max-w-[95%]" wire:key="m-{{ $loop->index }}" data-tour="assistant-answer">
                        <div class="whitespace-pre-line break-words rounded-2xl rounded-bl-md bg-slate-100 px-3 py-2 text-sm leading-relaxed text-gray-800">{{ $message['text'] }}</div>
                        @if (! empty($message['links']))
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($message['links'] as $link)
                                    <li>
                                        <a
                                            href="{{ $link['url'] }}"
                                            @if ($link['kind'] === 'file') target="_blank" rel="noopener" @endif
                                            class="flex items-start gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50"
                                        >
                                            @if ($link['kind'] === 'file')
                                                <x-heroicon-o-document-text class="mt-0.5 h-4 w-4 shrink-0 text-brand-700" />
                                            @elseif ($link['kind'] === 'page')
                                                <x-heroicon-o-arrow-right class="mt-0.5 h-4 w-4 shrink-0 text-brand-700" />
                                            @else
                                                <x-heroicon-o-table-cells class="mt-0.5 h-4 w-4 shrink-0 text-brand-700" />
                                            @endif
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-medium text-gray-900">{{ $link['title'] }}</span>
                                                @if ($link['meta'])
                                                    <span class="block truncate text-xs text-gray-500">{{ $link['meta'] }}</span>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if (! empty($message['engine']))
                            <p class="mt-1 text-[11px] text-gray-400">{{ $message['engine'] }}. Periksa kembali sebelum dipakai.</p>
                        @endif
                    </div>
                @endif
            @endforeach

            <template x-if="pending">
                <div class="space-y-4">
                    <div class="flex justify-end">
                        <p class="max-w-[85%] whitespace-pre-line break-words rounded-2xl rounded-br-md bg-brand-700 px-3 py-2 text-sm text-white" x-text="pending"></p>
                    </div>
                    <p class="text-sm text-gray-500">Mencari jawaban…</p>
                </div>
            </template>
        </div>

        <form @submit.prevent="ask()" class="border-t border-slate-200 p-3">
            <div class="flex items-end gap-2">
                <textarea
                    x-ref="input"
                    x-model="draft"
                    rows="1"
                    maxlength="500"
                    placeholder="Tulis pertanyaan…"
                    data-tour="assistant-input"
                    @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); ask() }"
                    class="max-h-32 min-h-[2.5rem] flex-1 resize-none rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100"
                ></textarea>
                <button type="submit" :disabled="pending !== '' || draft.trim().length < 2" title="Kirim" class="rounded-full bg-brand-700 p-2.5 text-white hover:bg-brand-800 disabled:opacity-50">
                    <x-heroicon-o-paper-airplane class="h-4 w-4" />
                </button>
            </div>
        </form>
    </section>
</div>
