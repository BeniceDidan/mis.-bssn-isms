<div class="glass-panel rounded-2xl p-6 shadow-lg transition-all duration-300 ease-spring hover:shadow-xl hover:-translate-y-1">
    <h2 class="text-sm font-semibold text-gray-900">Ubah Kata Sandi</h2>
    <p class="mt-1 text-sm text-gray-500">Gunakan kata sandi yang kuat dan tidak digunakan di tempat lain.</p>

    <form wire:submit="updatePassword" class="mt-4 space-y-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi Saat Ini</label>
            <input type="password" wire:model="current_password" autocomplete="current-password" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
            @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi Baru</label>
            <input type="password" wire:model="password" autocomplete="new-password" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Konfirmasi Kata Sandi Baru</label>
            <input type="password" wire:model="password_confirmation" autocomplete="new-password" class="w-full rounded-lg border-white/60 bg-white/50 text-sm transition-all duration-300 ease-spring focus:scale-[1.01] focus:border-sky-400 focus:ring-sky-400">
        </div>

        <div>
            <button type="submit" x-ripple class="rounded-full bg-gradient-to-r from-sky-500 to-sky-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-sky-500/30 transition-all duration-300 ease-spring hover:scale-105 hover:shadow-sky-500/50 active:scale-95">
                <span wire:loading.remove wire:target="updatePassword">Perbarui Kata Sandi</span>
                <span wire:loading wire:target="updatePassword">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
