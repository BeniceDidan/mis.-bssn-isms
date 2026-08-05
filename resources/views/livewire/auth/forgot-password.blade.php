<div class="w-full max-w-sm">
    <div class="mb-6 text-center">
        <h1 class="text-lg font-semibold text-gray-900">Lupa Kata Sandi?</h1>
        <p class="mt-1 text-sm text-gray-500">Masukkan email akun Anda, kami kirimkan tautan untuk mengatur ulang kata sandi.</p>
    </div>

    @if ($status)
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendResetLink" class="glass-panel space-y-4 rounded-2xl p-6 shadow-2xl">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
            <input
                type="email"
                wire:model="email"
                autofocus
                autocomplete="email"
                class="w-full rounded-lg border border-white/80 bg-white/70 px-3 py-2 text-sm shadow-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:bg-white/90 focus:ring-sky-400"
            >
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <button
            x-ripple
            type="submit"
            class="w-full rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-[1.02] hover:shadow-sky-500/50 active:scale-95"
        >
            <span wire:loading.remove wire:target="sendResetLink">Kirim Tautan Reset</span>
            <span wire:loading wire:target="sendResetLink">Mengirim...</span>
        </button>

        <button
            type="button"
            @click="mode = 'signin'"
            class="w-full text-center text-sm font-medium text-sky-600 transition-colors duration-150 hover:text-sky-700"
        >
            &larr; Kembali ke halaman masuk
        </button>
    </form>
</div>
