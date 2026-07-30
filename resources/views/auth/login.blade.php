<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk &mdash; BSSN ISMS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="relative flex h-full items-center justify-center overflow-hidden bg-gradient-to-br from-sky-100 via-white to-indigo-100 font-sans antialiased">
    <div class="pointer-events-none fixed inset-0 -z-20 overflow-hidden">
        <div class="blob-float absolute -left-24 -top-24 h-96 w-96 rounded-full bg-sky-300/50 blur-3xl"></div>
        <div class="blob-float-delay absolute -right-20 top-1/4 h-80 w-80 rounded-full bg-purple-300/40 blur-3xl"></div>
        <div class="blob-float-slow absolute bottom-0 left-1/3 h-96 w-96 rounded-full bg-teal-200/50 blur-3xl"></div>
    </div>

    {{-- Interactive particle field — see interactiveBackground() in app.js.
         Sits above the blurred blobs (for texture) but below the login
         card (z-10), and doesn't intercept clicks. --}}
    <div class="pointer-events-none fixed inset-0 -z-10" x-data="interactiveBackground()">
        <canvas x-ref="canvas" class="h-full w-full"></canvas>
    </div>

    {{-- Floating Kominfo/telecom-domain icons — signal, jaringan, keamanan
         data — so the background reads as "communications & informatics"
         rather than an empty gradient. Three nested layers per icon, each
         owning its own transform so they don't fight each other: outer =
         JS-driven parallax shift (parallaxField() in app.js, "depth" is
         just the px multiplier baked into each :style), middle = one-shot
         pop-in on page load (icon-intro), inner = the continuous slow bob
         (icon-float). Each chip also has pointer-events-auto so it reacts
         to a direct hover, unlike the fully click-through parent layer.
         Scattered around the edges so nothing sits behind the card. --}}
    <div
        class="pointer-events-none fixed inset-0 -z-10 overflow-hidden"
        x-data="parallaxField()"
        @mousemove.window="onMove($event)"
    >
        <div class="absolute left-[6%] top-[10%] hidden sm:block" :style="`transform: translate(${mx * 30}px, ${my * 30}px)`">
            <div class="icon-intro" style="animation-delay: 0.05s">
                <div class="icon-float pointer-events-auto flex h-16 w-16 cursor-default items-center justify-center rounded-2xl border border-white/60 bg-white/40 text-sky-600/70 shadow-lg shadow-sky-900/5 backdrop-blur-sm transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/70 hover:text-sky-700" style="animation-delay: 0s">
                    <x-heroicon-o-signal class="h-8 w-8" />
                </div>
            </div>
        </div>

        <div class="absolute right-[8%] top-[8%]" :style="`transform: translate(${mx * 45}px, ${my * 45}px)`">
            <div class="icon-intro" style="animation-delay: 0.15s">
                <div class="icon-float pointer-events-auto flex h-20 w-20 cursor-default items-center justify-center rounded-2xl border border-white/60 bg-white/40 text-indigo-600/70 shadow-lg shadow-indigo-900/5 backdrop-blur-sm transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/70 hover:text-indigo-700" style="animation-delay: -1.5s">
                    <x-heroicon-o-wifi class="h-10 w-10" />
                </div>
            </div>
        </div>

        <div class="absolute bottom-[14%] left-[9%]" :style="`transform: translate(${mx * 25}px, ${my * 25}px)`">
            <div class="icon-intro" style="animation-delay: 0.3s">
                <div class="icon-float pointer-events-auto flex h-24 w-24 cursor-default items-center justify-center rounded-2xl border border-white/60 bg-white/40 text-teal-600/70 shadow-lg shadow-teal-900/5 backdrop-blur-sm transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/70 hover:text-teal-700" style="animation-delay: -3s">
                    <x-heroicon-o-globe-asia-australia class="h-12 w-12" />
                </div>
            </div>
        </div>

        <div class="absolute bottom-[10%] right-[7%]" :style="`transform: translate(${mx * 35}px, ${my * 35}px)`">
            <div class="icon-intro" style="animation-delay: 0.2s">
                <div class="icon-float pointer-events-auto flex h-[4.5rem] w-[4.5rem] cursor-default items-center justify-center rounded-2xl border border-white/60 bg-white/40 text-sky-700/70 shadow-lg shadow-sky-900/5 backdrop-blur-sm transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/70 hover:text-sky-800" style="animation-delay: -2s">
                    <x-heroicon-o-server-stack class="h-9 w-9" />
                </div>
            </div>
        </div>

        <div class="absolute left-[3%] top-[45%] hidden md:block" :style="`transform: translate(${mx * 50}px, ${my * 50}px)`">
            <div class="icon-intro" style="animation-delay: 0.4s">
                <div class="icon-float pointer-events-auto flex h-14 w-14 cursor-default items-center justify-center rounded-2xl border border-white/60 bg-white/40 text-emerald-600/70 shadow-lg shadow-emerald-900/5 backdrop-blur-sm transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/70 hover:text-emerald-700" style="animation-delay: -4s">
                    <x-heroicon-o-shield-check class="h-7 w-7" />
                </div>
            </div>
        </div>

        <div class="absolute right-[5%] top-[42%] hidden md:block" :style="`transform: translate(${mx * 40}px, ${my * 40}px)`">
            <div class="icon-intro" style="animation-delay: 0.35s">
                <div class="icon-float pointer-events-auto flex h-16 w-16 cursor-default items-center justify-center rounded-2xl border border-white/60 bg-white/40 text-indigo-600/70 shadow-lg shadow-indigo-900/5 backdrop-blur-sm transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/70 hover:text-indigo-700" style="animation-delay: -0.8s">
                    <x-heroicon-o-cpu-chip class="h-8 w-8" />
                </div>
            </div>
        </div>

        <div class="absolute left-[22%] top-[6%] hidden sm:block" :style="`transform: translate(${mx * 20}px, ${my * 20}px)`">
            <div class="icon-intro" style="animation-delay: 0.25s">
                <div class="icon-float pointer-events-auto flex h-14 w-14 cursor-default items-center justify-center rounded-2xl border border-white/60 bg-white/40 text-sky-500/70 shadow-lg shadow-sky-900/5 backdrop-blur-sm transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/70 hover:text-sky-600" style="animation-delay: -2.6s">
                    <x-heroicon-o-cloud class="h-7 w-7" />
                </div>
            </div>
        </div>

        <div class="absolute bottom-[6%] right-[20%] hidden sm:block" :style="`transform: translate(${mx * 55}px, ${my * 55}px)`">
            <div class="icon-intro" style="animation-delay: 0.1s">
                <div class="icon-float pointer-events-auto flex h-14 w-14 cursor-default items-center justify-center rounded-2xl border border-white/60 bg-white/40 text-purple-600/70 shadow-lg shadow-purple-900/5 backdrop-blur-sm transition-all duration-300 ease-spring hover:scale-110 hover:bg-white/70 hover:text-purple-700" style="animation-delay: -1.2s">
                    <x-heroicon-o-radio class="h-7 w-7" />
                </div>
            </div>
        </div>
    </div>

    <livewire:auth.login />

    @livewireScripts
</body>
</html>
