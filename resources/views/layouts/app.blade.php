<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $title ?? 'Dashboard') &mdash; BSSN ISMS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Required by any element using x-cloak (create/profile dropdowns,
         mobile sidebar overlay) so Alpine hides them before it boots. --}}
    <style>[x-cloak] { display: none !important; }</style>

    {{-- Sets .dark on <html> before first paint, so there's no flash of
         the light theme while Alpine boots. Runs synchronously in <head>,
         not via Alpine, on purpose — Alpine's x-init fires after the DOM
         (and often first paint) already happened. Falls back to OS
         preference only when the user has never toggled it manually. --}}
    <script>
        (function () {
            var stored = localStorage.getItem('kominfo_isms_theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (! stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body
    class="h-full font-sans text-gray-900 antialiased dark:text-gray-100"
    x-data="{ sidebarOpen: false, darkMode: document.documentElement.classList.contains('dark') }"
    x-init="$watch('darkMode', (value) => {
        document.documentElement.classList.toggle('dark', value);
        localStorage.setItem('kominfo_isms_theme', value ? 'dark' : 'light');
    })"
>

    @php
        // Which "place" the current page belongs to — drives both the
        // ambient blob palette below and the hand-composed illustration in
        // components/module-ornament.blade.php. Pages outside the 8
        // modules (Dashboard, Panduan, Verifikasi, Profil) keep the
        // original neutral mix — there's no single module identity to
        // lend them. Full literal Tailwind classes (not interpolated) so
        // the JIT scanner picks them up.
        $moduleBlobs = [
            'sdm' => ['bg-slate-300/40', 'bg-sky-200/30', 'bg-slate-200/40'],
            'pengetahuan' => ['bg-amber-300/40', 'bg-orange-200/30', 'bg-yellow-200/40'],
            'aset' => ['bg-teal-300/40', 'bg-cyan-200/30', 'bg-teal-200/40'],
            'keamanan' => ['bg-emerald-300/40', 'bg-green-200/30', 'bg-emerald-200/40'],
            'risiko' => ['bg-purple-300/40', 'bg-fuchsia-200/30', 'bg-purple-200/40'],
            'perubahan' => ['bg-indigo-300/40', 'bg-blue-200/30', 'bg-indigo-200/40'],
            'layanan' => ['bg-rose-300/40', 'bg-pink-200/30', 'bg-rose-200/40'],
            'data' => ['bg-sky-300/40', 'bg-cyan-200/30', 'bg-sky-200/40'],
        ];

        $currentModule = match (true) {
            request()->routeIs('hr-risks.*') => 'sdm',
            request()->routeIs('knowledge.*') => 'pengetahuan',
            request()->routeIs('assets.*') => 'aset',
            request()->routeIs('security-programs.*') => 'keamanan',
            request()->routeIs('risks.*') => 'risiko',
            request()->routeIs('changes.*') => 'perubahan',
            request()->routeIs('services.*') => 'layanan',
            request()->routeIs('data-information.*') => 'data',
            default => null,
        };

        $blobs = $moduleBlobs[$currentModule] ?? null;
    @endphp

    <div class="relative flex h-full overflow-hidden">
        {{-- Ambient drifting color blobs the frosted-glass panels blur
             against — fixed behind everything, never intercepts clicks.
             Palette swaps per module; the illustration itself lives lower
             down, inside the scrolling content column, so it can peek out
             from behind the first glass-panel card instead of floating
             disconnected in the fixed viewport corner. --}}
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-gradient-to-br from-sky-50 via-white to-indigo-50 dark:from-slate-950 dark:via-slate-950 dark:to-indigo-950/60">
            <div class="blob-float absolute -left-24 -top-24 h-96 w-96 rounded-full blur-3xl {{ $blobs[0] ?? 'bg-sky-300/40' }}"></div>
            <div class="blob-float-delay absolute right-0 top-1/3 h-80 w-80 rounded-full blur-3xl {{ $blobs[1] ?? 'bg-purple-300/30' }}"></div>
            <div class="blob-float-slow absolute bottom-0 left-1/3 h-96 w-96 rounded-full blur-3xl {{ $blobs[2] ?? 'bg-teal-200/40' }}"></div>
        </div>

        <x-layout.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-layout.topbar />

            <main class="flex-1 overflow-y-auto p-6">
                <div class="relative mx-auto max-w-screen-2xl space-y-6">
                    <x-module-ornament :module="$currentModule" />

                    <div class="relative z-10 space-y-6">
                        @isset($slot)
                            {{ $slot }}
                        @else
                            @yield('content')
                        @endisset
                    </div>
                </div>
            </main>
        </div>
    </div>

    <x-confirm-sheet />
    <livewire:global-search />

    @livewireScripts
    @stack('scripts')
</body>
</html>
