@php
    // Route names are placeholders wired up as each module is built —
    // guarded with Route::has() below so the layout renders correctly
    // before those routes exist. This list matches the actual 8 modules
    // Kominfo has source data for (Downloads folder files 1, 3-8 — "2" was
    // never provided as a file, so Risiko fills that slot), not the generic
    // ISMS module names first assumed before the real files were seen.
    // Order follows the 8-module correlation flow (see guide/index.blade.php's
    // $flowSteps — same sequence, single source of truth for "which comes
    // first"): SDM & Pengetahuan bekali orang/pengetahuannya, Aset jadi
    // pusat data, lalu Keamanan/Risiko/Perubahan/Layanan/Data & Informasi
    // beroperasi di atas aset itu satu per satu.
    $modules = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'o-home', 'enabled' => true],
        ['label' => 'Manajemen SDM', 'route' => 'hr-risks.index', 'icon' => 'o-users', 'enabled' => true],
        ['label' => 'Manajemen Pengetahuan', 'route' => 'knowledge.index', 'icon' => 'o-light-bulb', 'enabled' => true],
        ['label' => 'Manajemen Aset', 'route' => 'assets.index', 'icon' => 'o-archive-box', 'enabled' => true],
        ['label' => 'Manajemen Keamanan Informasi', 'route' => 'security-programs.index', 'icon' => 'o-shield-check', 'enabled' => true],
        ['label' => 'Manajemen Risiko', 'route' => 'risks.index', 'icon' => 'o-exclamation-triangle', 'enabled' => true],
        ['label' => 'Manajemen Perubahan', 'route' => 'changes.index', 'icon' => 'o-arrow-path', 'enabled' => true],
        ['label' => 'Manajemen Layanan', 'route' => 'services.index', 'icon' => 'o-wrench-screwdriver', 'enabled' => true],
        ['label' => 'Manajemen Data Informasi', 'route' => 'data-information.index', 'icon' => 'o-circle-stack', 'enabled' => true],
    ];
@endphp

<aside
    class="glass-panel-dark fixed inset-y-0 left-0 z-40 flex w-64 flex-col text-slate-300 transition-transform duration-300 ease-spring md:static md:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex h-16 items-center gap-2.5 border-b border-white/10 px-5">
        {{-- Same logo asset as the login page (public/images/kominfo-logo.png)
             — real Kominfo swirl mark instead of the generic "K" square,
             with the same onerror fallback if the file's ever missing. --}}
        <div class="relative flex h-9 w-9 shrink-0 items-center justify-center transition-transform duration-300 ease-spring hover:scale-110">
            <img
                src="{{ asset('images/kominfo-logo.png') }}"
                alt="Logo Kominfo"
                class="h-9 w-9 object-contain drop-shadow-[0_2px_6px_rgba(0,0,0,0.4)]"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"
            >
            <div class="hidden h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-sky-400 to-sky-600 text-sm font-bold text-white shadow-lg shadow-sky-900/50">K</div>
        </div>
        <div class="leading-tight">
            <p class="text-sm font-semibold text-white">BSSN ISMS</p>
            <p class="text-[11px] text-slate-400">Information Security</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($modules as $module)
            @php
                $isRoutable = $module['enabled'] && $module['route'] && \Illuminate\Support\Facades\Route::has($module['route']);
                $isActive = $isRoutable && request()->routeIs($module['route']);
            @endphp

            @if ($module['enabled'])
                <a
                    href="{{ $isRoutable ? route($module['route']) : '#' }}"
                    x-ripple
                    @class([
                        'group relative flex items-center gap-3 overflow-hidden rounded-lg px-3 py-2 text-sm font-medium transition-all duration-300 ease-spring',
                        'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-900/50 scale-[1.02]' => $isActive,
                        'text-slate-300 hover:translate-x-1.5 hover:scale-[1.02] hover:bg-white/10 hover:text-white' => ! $isActive,
                    ])
                >
                    <span
                        @class([
                            'absolute inset-y-0 left-0 w-1 rounded-r bg-sky-300 transition-transform duration-300 ease-spring',
                            'scale-y-100' => $isActive,
                            'scale-y-0' => ! $isActive,
                        ])
                    ></span>
                    <x-dynamic-component
                        :component="'heroicon-' . $module['icon']"
                        class="h-5 w-5 shrink-0 transition-transform duration-300 ease-spring group-hover:scale-125 group-hover:-rotate-6"
                    />
                    <span>{{ $module['label'] }}</span>
                </a>
            @else
                <div class="flex cursor-not-allowed items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition-colors duration-200 hover:bg-white/5">
                    <span class="flex items-center gap-3">
                        <x-dynamic-component :component="'heroicon-' . $module['icon']" class="h-5 w-5 shrink-0" />
                        {{ $module['label'] }}
                    </span>
                    <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                        Soon
                    </span>
                </div>
            @endif
        @endforeach

        @if (auth()->user()?->isAdmin() && \Illuminate\Support\Facades\Route::has('verifikasi'))
            @php
                $pendingCount = collect([
                        \App\Models\Asset::class, \App\Models\Risk::class, \App\Models\Change::class,
                        \App\Models\DataInformation::class, \App\Models\HrRisk::class, \App\Models\KnowledgeAsset::class,
                        \App\Models\Service::class,
                        \App\Models\KnowledgeExpert::class, \App\Models\KnowledgeActivity::class,
                        \App\Models\KnowledgeRisk::class, \App\Models\SecurityProgram::class,
                    ])
                    ->sum(fn ($modelClass) => $modelClass::where('verification_status', 'menunggu_verifikasi')->count());
                $isVerifikasiActive = request()->routeIs('verifikasi');
            @endphp
            <div class="my-2 border-t border-white/10 pt-2">
                <a
                    href="{{ route('verifikasi') }}"
                    x-ripple
                    @class([
                        'group relative flex items-center justify-between gap-3 overflow-hidden rounded-lg px-3 py-2 text-sm font-medium transition-all duration-300 ease-spring',
                        'bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-lg shadow-amber-900/50 scale-[1.02]' => $isVerifikasiActive,
                        'text-slate-300 hover:translate-x-1.5 hover:scale-[1.02] hover:bg-white/10 hover:text-white' => ! $isVerifikasiActive,
                    ])
                >
                    <span class="flex items-center gap-3">
                        <x-heroicon-o-clipboard-document-check class="h-5 w-5 shrink-0 transition-transform duration-300 ease-spring group-hover:scale-125 group-hover:-rotate-6" />
                        Verifikasi
                    </span>
                    @if ($pendingCount > 0)
                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">
                            {{ $pendingCount }}
                        </span>
                    @endif
                </a>
            </div>
        @endif
    </nav>

    <div class="border-t border-white/10 px-4 py-3 text-[11px] text-slate-400">
        v1.0.0 &middot; 8/8 modul aktif
    </div>
</aside>

<div
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    @click="sidebarOpen = false"
    class="fixed inset-x-0 bottom-0 top-16 z-30 bg-slate-900/50 backdrop-blur-sm md:hidden"
></div>
