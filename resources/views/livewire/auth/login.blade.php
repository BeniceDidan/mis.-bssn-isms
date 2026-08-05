<div
    class="relative z-10 w-full max-w-sm"
    x-data="{ shown: false }"
    x-init="setTimeout(() => shown = true, 50)"
    x-transition:enter="transition ease-spring duration-700"
    x-transition:enter-start="opacity-0 translate-y-8 scale-90"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-show="shown"
>
    <div class="mb-6 flex flex-col items-center gap-2 text-center">
        {{-- The actual Kominfo logo, background-stripped and trimmed to just
             the swirl mark (public/images/kominfo-logo.png, generated once
             from the JPEG you sent — not baked into the page as a flat
             photo anymore, so it floats on the gradient like a native
             vector graphic instead of reading as "a pasted image"). If it's
             ever missing, onerror swaps to a plain "K" monogram rather than
             a guessed redraw. Either way it sits inside the same
             signal-pulse + radar-sweep rings. --}}
        <div class="relative mb-2 flex h-24 w-24 items-center justify-center">
            <span class="radar-sweep absolute -inset-2 rounded-full border-2 border-dashed border-sky-300/70"></span>

            <span class="signal-pulse absolute inset-0 rounded-full border-2 border-sky-400"></span>
            <span class="signal-pulse absolute inset-0 rounded-full border-2 border-indigo-400" style="animation-delay: -0.9s"></span>
            <span class="signal-pulse absolute inset-0 rounded-full border-2 border-sky-400" style="animation-delay: -1.8s"></span>

            <img
                src="{{ asset('images/kominfo-logo.png') }}"
                alt="Logo Kominfo"
                class="relative h-16 w-16 object-contain drop-shadow-[0_6px_14px_rgba(30,58,138,0.4)] transition-transform duration-500 ease-spring hover:scale-110"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"
            >
            <div class="relative hidden h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-sky-400 to-blue-700 text-2xl font-bold text-white shadow-lg shadow-sky-500/40 transition-transform duration-500 ease-spring hover:scale-110">
                K
            </div>
        </div>

        <h1 class="text-lg font-semibold text-gray-900">BSSN ISMS</h1>
        <p class="text-sm text-gray-500">Masuk untuk mengelola aset, risiko, dan keamanan informasi</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form
        wire:submit="authenticate"
        x-data="tiltCard()"
        @mousemove="onMove($event)"
        @mouseleave="onLeave()"
        :style="`transform: perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg)`"
        class="glass-panel space-y-4 rounded-2xl p-6 shadow-2xl transition-transform duration-300 ease-out hover:shadow-sky-500/10">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Email atau Username</label>
            <input
                type="text"
                wire:model="login"
                autofocus
                autocomplete="username"
                class="w-full rounded-lg border border-white/80 bg-white/70 px-3 py-2 text-sm shadow-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:bg-white/90 focus:ring-sky-400"
            >
            @error('login') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi</label>
            <input
                type="password"
                wire:model="password"
                autocomplete="current-password"
                class="w-full rounded-lg border border-white/80 bg-white/70 px-3 py-2 text-sm shadow-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:bg-white/90 focus:ring-sky-400"
            >
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model="remember" class="rounded border-gray-300 text-sky-600 transition-colors duration-150 focus:ring-sky-500">
                Ingat saya
            </label>
            <button type="button" @click="mode = 'forgot'" class="text-sm font-medium text-sky-600 transition-colors duration-150 hover:text-sky-700">
                Lupa kata sandi?
            </button>
        </div>

        <button
            x-ripple
            type="submit"
            class="w-full rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-[1.02] hover:shadow-sky-500/50 active:scale-95"
        >
            <span wire:loading.remove wire:target="authenticate">Masuk</span>
            <span wire:loading wire:target="authenticate">Memproses...</span>
        </button>
    </form>
</div>
