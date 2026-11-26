{{--
    Isian yang jarang dipakai dikumpulkan di bawah dan dilipat, supaya formulir
    pendek (masukan pengguna 26 Nov 2026: "sederhanakan isian formulir").
    Tutorial modul (resources/js/tour.js) membuka lipatan ini sendiri lewat
    data-form-more-toggle saat menunjuk kolom Kode Personil di dalamnya.
--}}
<div x-data="{ more: false }" data-form-more class="border-t border-slate-200 pt-3">
    <button type="button" @click="more = ! more" data-form-more-toggle class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:text-brand-800">
        <span x-text="more ? 'Sembunyikan isian lain' : 'Isian lain (opsional)'">Isian lain (opsional)</span>
        <x-heroicon-o-chevron-down class="h-4 w-4 transition" x-bind:class="more && 'rotate-180'" />
    </button>
    <div x-show="more" x-cloak class="mt-4 space-y-4">
        {{ $slot }}
    </div>
</div>
