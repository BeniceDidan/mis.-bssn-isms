<div
    x-data
    x-show="$store.confirm.open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
    @click.self="$store.confirm.cancel()"
    @keydown.escape.window="$store.confirm.cancel()"
>
    <div
        x-show="$store.confirm.open"
        x-transition:enter="transition ease-spring duration-400"
        x-transition:enter-start="opacity-0 scale-75 translate-y-6"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-90 translate-y-4"
        class="glass-panel w-full max-w-sm rounded-2xl p-6 text-center shadow-2xl"
    >
        <div
            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full transition-colors duration-300"
            x-bind:class="$store.confirm.tone === 'danger' ? 'bg-red-100' : 'bg-sky-100'"
        >
            <x-heroicon-o-question-mark-circle
                class="h-6 w-6 transition-colors duration-300"
                x-bind:class="$store.confirm.tone === 'danger' ? 'text-red-600' : 'text-sky-600'"
            />
        </div>

        <h3 class="mt-3 text-base font-semibold text-gray-900" x-text="$store.confirm.title"></h3>
        <p class="mt-1 text-sm text-gray-500" x-text="$store.confirm.message"></p>

        <div class="mt-5 flex gap-2">
            <button
                type="button"
                x-ripple
                @click="$store.confirm.cancel()"
                class="flex-1 rounded-full border border-white/60 bg-white/40 px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/70 active:scale-95"
            >
                Batal
            </button>
            <button
                type="button"
                x-ripple
                @click="$store.confirm.confirm()"
                class="flex-1 rounded-full px-4 py-2 text-sm font-medium text-white shadow-lg transition-all duration-300 ease-spring hover:scale-105 active:scale-95"
                x-bind:class="$store.confirm.tone === 'danger'
                    ? 'bg-gradient-to-r from-red-500 to-red-600 shadow-red-500/30 hover:shadow-red-500/50'
                    : 'bg-gradient-to-r from-sky-500 to-sky-600 shadow-sky-500/30 hover:shadow-sky-500/50'"
            >
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>
