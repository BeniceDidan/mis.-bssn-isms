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

    {{-- Gaya pemerintahan: latar abu-biru polos, panel putih solid, satu
         warna aksen biru tua. Gumpalan warna per modul dan ilustrasi
         ornamen sengaja dihapus (November 2026). --}}
    <div class="relative flex h-full overflow-hidden bg-slate-100 dark:bg-slate-950">
        <x-layout.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-layout.topbar />

            <main class="flex-1 overflow-y-auto p-6">
                <div class="relative mx-auto max-w-screen-2xl space-y-6">
                    <div class="relative space-y-6">
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
