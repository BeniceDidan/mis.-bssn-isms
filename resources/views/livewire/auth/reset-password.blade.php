<div class="relative z-10 w-full max-w-sm">
    <div class="mb-6 flex flex-col items-center gap-2 text-center">
        <img src="{{ asset('images/kominfo-logo.png') }}" alt="Logo Kominfo" class="h-16 w-16 object-contain" onerror="this.style.display='none'">
        <h1 class="text-lg font-semibold text-gray-900">Atur Ulang Kata Sandi</h1>
        <p class="text-sm text-gray-500">Buat kata sandi baru untuk akun {{ $email }}</p>
    </div>

    <form wire:submit="resetPassword" class="glass-panel space-y-4 rounded-2xl p-6 shadow-2xl">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
            <input
                type="email"
                wire:model="email"
                autocomplete="email"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-brand-400 focus:bg-white focus:ring-brand-400"
            >
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi Baru</label>
            <input
                type="password"
                wire:model="password"
                autofocus
                autocomplete="new-password"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-brand-400 focus:bg-white focus:ring-brand-400"
            >
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Ulangi Kata Sandi Baru</label>
            <input
                type="password"
                wire:model="password_confirmation"
                autocomplete="new-password"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-brand-400 focus:bg-white focus:ring-brand-400"
            >
        </div>

        <button
            x-ripple
            type="submit"
            class="w-full rounded-full bg-brand-700 px-4 py-2.5 text-sm font-medium text-white shadow-lg transition-all duration-300 ease-spring hover:scale-[1.02] active:scale-95"
        >
            <span wire:loading.remove wire:target="resetPassword">Simpan Kata Sandi Baru</span>
            <span wire:loading wire:target="resetPassword">Menyimpan...</span>
        </button>
    </form>
</div>
