<header class="glass-panel sticky top-0 z-20 flex h-16 items-center gap-4 border-x-0 border-t-0 px-6">
    <button
        type="button"
        x-ripple
        class="rounded-lg p-1.5 text-gray-500 transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/60 hover:text-gray-700 active:scale-90 md:hidden"
        @click="sidebarOpen = !sidebarOpen"
    >
        <x-heroicon-o-bars-3 class="h-6 w-6" />
    </button>

    <div class="max-w-xl flex-1">
        <label for="global-search" class="sr-only">Cari aset, risiko, atau data lainnya</label>
        <livewire:global-search />
    </div>

    {{-- ml-auto pins this to the header's right edge regardless of how much
         of its max-w-xl cap the search box actually uses — without it, the
         leftover space between the capped search box and this group just
         sits unclaimed to the right of the buttons instead of before them. --}}
    <div class="ml-auto flex items-center gap-3" x-data="{ createOpen: false, profileOpen: false }">
        <button
            type="button"
            x-ripple
            @click="darkMode = ! darkMode"
            :title="darkMode ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
            class="relative rounded-full p-2 text-gray-500 transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/60 hover:text-sky-600 active:scale-90"
        >
            <x-heroicon-o-sun
                class="h-5 w-5 transition-all duration-300 ease-spring"
                x-show="darkMode"
                x-cloak
                x-transition:enter="transition ease-spring duration-400"
                x-transition:enter-start="opacity-0 -rotate-90 scale-50"
                x-transition:enter-end="opacity-100 rotate-0 scale-100"
            />
            <x-heroicon-o-moon
                class="h-5 w-5 transition-all duration-300 ease-spring"
                x-show="! darkMode"
                x-cloak
                x-transition:enter="transition ease-spring duration-400"
                x-transition:enter-start="opacity-0 rotate-90 scale-50"
                x-transition:enter-end="opacity-100 rotate-0 scale-100"
            />
        </button>

        <a
            href="{{ \Illuminate\Support\Facades\Route::has('guide.index') ? route('guide.index') : '#' }}"
            x-ripple
            title="Panduan Penggunaan"
            class="rounded-full p-2 text-gray-500 transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/60 hover:text-sky-600 active:scale-90"
        >
            <x-heroicon-o-question-mark-circle class="h-5 w-5" />
        </a>

        @if (auth()->user()?->canWrite())
            <div class="relative">
                <button
                    x-ripple
                    @click="createOpen = !createOpen"
                    @click.outside="createOpen = false"
                    class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-3 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-sky-500/50 active:scale-95"
                >
                    <x-heroicon-o-plus class="h-4 w-4 transition-transform duration-300 ease-spring" x-bind:class="createOpen ? 'rotate-90' : ''" />
                    Buat Baru
                    <x-heroicon-o-chevron-down class="h-3.5 w-3.5 transition-transform duration-300 ease-spring" x-bind:class="createOpen ? 'rotate-180' : ''" />
                </button>

                <div
                    x-show="createOpen"
                    x-transition:enter="transition ease-spring duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-3 scale-90"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 -translate-y-2 scale-90"
                    x-cloak
                    class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-xl border border-white/60 bg-white/95 py-1 shadow-2xl backdrop-blur-xl"
                >
                    {{-- Solid-ish background (not the usual .glass-panel
                         translucency) on purpose — this floats directly over
                         the topbar's own icons, and the normal frosted-glass
                         look let them show through and read as visual noise
                         when the menu was open. --}}
                    {{-- The create-modals only mount on their own index pages, so
                         these shortcuts navigate there with a query flag rather
                         than dispatching an event that may have no listener on
                         the current page. --}}
                    <a href="{{ \Illuminate\Support\Facades\Route::has('assets.index') ? route('assets.index', ['create' => 1]) : '#' }}" class="block px-4 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700">Aset Baru</a>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('risks.index') ? route('risks.index', ['create' => 1]) : '#' }}" class="block px-4 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700">Risiko Baru</a>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('changes.index') ? route('changes.index', ['create' => 1]) : '#' }}" class="block px-4 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700">Perubahan Baru</a>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('data-information.index') ? route('data-information.index', ['create' => 1]) : '#' }}" class="block px-4 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700">Catatan Data Baru</a>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('hr-risks.index') ? route('hr-risks.index', ['create' => 1]) : '#' }}" class="block px-4 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700">Risiko SDM Baru</a>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('knowledge.index') ? route('knowledge.index', ['create' => 1]) : '#' }}" class="block px-4 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700">Dokumen Pengetahuan Baru</a>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('services.index') ? route('services.index', ['create' => 1]) : '#' }}" class="block px-4 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700">Layanan Baru</a>
                </div>
            </div>
        @endif

        <div class="relative">
            <button
                x-ripple
                @click="profileOpen = !profileOpen"
                @click.outside="profileOpen = false"
                class="flex items-center gap-2 rounded-full p-1 transition-all duration-300 ease-spring hover:scale-105 hover:bg-white/60 active:scale-95"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-slate-600 to-slate-800 text-xs font-semibold text-white shadow-md transition-transform duration-300 ease-spring hover:scale-110">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <span class="hidden text-sm font-medium text-gray-700 sm:block">
                    {{ auth()->user()->name ?? 'User' }}
                </span>
                <x-heroicon-o-chevron-down class="hidden h-3.5 w-3.5 text-gray-400 transition-transform duration-300 ease-spring sm:block" x-bind:class="profileOpen ? 'rotate-180' : ''" />
            </button>

            <div
                x-show="profileOpen"
                x-transition:enter="transition ease-spring duration-300"
                x-transition:enter-start="opacity-0 -translate-y-3 scale-90"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-2 scale-90"
                x-cloak
                class="absolute right-0 z-50 mt-2 w-44 origin-top-right rounded-xl border border-white/60 bg-white/95 py-1 shadow-2xl backdrop-blur-xl"
            >
                <a href="{{ \Illuminate\Support\Facades\Route::has('guide.index') ? route('guide.index') : '#' }}" class="block px-4 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700">Panduan Penggunaan</a>
                <a href="{{ \Illuminate\Support\Facades\Route::has('profile.edit') ? route('profile.edit') : '#' }}" class="block px-4 py-2 text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-sky-50 hover:text-sky-700">Profil</a>
                <form method="POST" action="{{ Illuminate\Support\Facades\Route::has('logout') ? route('logout') : '#' }}">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 transition-all duration-200 hover:translate-x-1 hover:bg-red-50 hover:text-red-600">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
