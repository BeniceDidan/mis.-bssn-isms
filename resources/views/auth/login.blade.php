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
<body class="relative h-full overflow-hidden bg-gradient-to-br from-sky-100 via-white to-indigo-100 font-sans antialiased">
    <div class="pointer-events-none fixed inset-0 -z-20 overflow-hidden">
        <div class="blob-float absolute -left-24 -top-24 h-96 w-96 rounded-full bg-sky-300/50 blur-3xl"></div>
        <div class="blob-float-delay absolute -right-20 top-1/4 h-80 w-80 rounded-full bg-purple-300/40 blur-3xl"></div>
        <div class="blob-float-slow absolute bottom-0 left-1/3 h-96 w-96 rounded-full bg-teal-200/50 blur-3xl"></div>
    </div>

    {{-- Interactive particle field — see interactiveBackground() in app.js. --}}
    <div class="pointer-events-none fixed inset-0 -z-10" x-data="interactiveBackground()">
        <canvas x-ref="canvas" class="h-full w-full"></canvas>
    </div>

    <div class="relative z-10 flex h-full items-center justify-center p-4">
        <div
            class="relative flex h-full w-full max-w-5xl overflow-hidden rounded-3xl bg-white/90 shadow-2xl lg:h-[640px] lg:max-h-[85vh]"
            x-data="{ shown: false, mode: 'signin' }"
            x-init="setTimeout(() => shown = true, 50)"
        >
            {{-- Left pane: illustration. Hidden below lg so mobile keeps a
                 plain full-width form. At lg+ it also slides to swap sides
                 with the form pane when `mode` toggles (mirrors the
                 reference GIF's Sign In / Sign Up swap — here it's
                 Sign In / Lupa Kata Sandi since this app has no self-serve
                 sign-up). --}}
            <div
                x-show="shown"
                x-transition:enter="transition ease-spring duration-700"
                x-transition:enter-start="opacity-0 -translate-x-16"
                x-transition:enter-end="opacity-100 translate-x-0"
                :class="mode === 'signin' ? '' : 'lg:translate-x-full'"
                class="relative hidden w-1/2 shrink-0 flex-col justify-between overflow-hidden bg-gradient-to-br from-sky-500 via-indigo-600 to-purple-600 p-10 transition-transform duration-[850ms] ease-spring lg:flex lg:absolute lg:inset-y-0 lg:left-0"
            >
                {{-- A few contained floating accent icons — same icon-float
                     bob used elsewhere on this page, just scoped to this
                     panel instead of scattered across the whole viewport. --}}
                <div class="absolute left-6 top-24 opacity-80 icon-float" style="animation-delay: -1s">
                    <x-heroicon-o-lock-closed class="h-6 w-6 text-white/70" />
                </div>
                <div class="absolute right-10 top-16 opacity-80 icon-float" style="animation-delay: -3s">
                    <x-heroicon-o-server-stack class="h-7 w-7 text-white/70" />
                </div>
                <div class="absolute bottom-24 left-10 opacity-80 icon-float" style="animation-delay: -4.5s">
                    <x-heroicon-o-cpu-chip class="h-6 w-6 text-white/70" />
                </div>

                <div class="relative z-10">
                    <h2 class="text-2xl font-semibold text-white">BSSN ISMS</h2>
                    <p class="mt-2 max-w-[24ch] text-sm text-sky-100/90">
                        Sistem manajemen keamanan informasi &mdash; aset, risiko, dan layanan TIK dalam satu tempat.
                    </p>
                </div>

                {{-- Original shield + lock + connected-nodes illustration —
                     stands in for the reference GIF's character art, themed
                     to cybersecurity instead. Nodes get a slow independent
                     bob (icon-float) so the whole scene feels alive without
                     any new keyframes. --}}
                <div class="relative z-10 flex flex-1 items-center justify-center">
                    <svg viewBox="0 0 400 400" class="icon-float h-56 w-56 drop-shadow-2xl" style="animation-duration: 5s;" role="img" aria-label="Ilustrasi keamanan siber">
                        <circle cx="200" cy="190" r="150" fill="white" opacity="0.07" />

                        {{-- Idle motion so the panel never looks frozen:
                             expanding pulse rings + a slow dashed sweep,
                             same pair already used around the login logo,
                             plus marching dashes along each node line to
                             read as "signal flowing in". --}}
                        <circle cx="200" cy="190" r="105" fill="none" stroke="white" stroke-opacity="0.35" stroke-width="2" class="signal-pulse" style="transform-origin: 200px 190px;" />
                        <circle cx="200" cy="190" r="105" fill="none" stroke="white" stroke-opacity="0.35" stroke-width="2" class="signal-pulse" style="transform-origin: 200px 190px; animation-delay: -1.3s;" />
                        <circle cx="200" cy="190" r="135" fill="none" stroke="white" stroke-opacity="0.25" stroke-width="1.5" stroke-dasharray="3 9" class="radar-sweep" style="transform-origin: 200px 190px;" />

                        <g class="icon-float" style="animation-delay: -2s; transform-origin: 70px 90px;">
                            <line class="line-flow" x1="140" y1="102" x2="72" y2="90" stroke="white" stroke-opacity="0.45" stroke-width="2" />
                            <circle cx="70" cy="90" r="15" fill="white" fill-opacity="0.12" />
                            <circle cx="70" cy="90" r="7" fill="white" />
                        </g>
                        <g class="icon-float" style="animation-delay: -0.5s; transform-origin: 330px 90px;">
                            <line class="line-flow" x1="260" y1="102" x2="328" y2="90" stroke="white" stroke-opacity="0.45" stroke-width="2" style="animation-delay: -0.4s;" />
                            <circle cx="330" cy="90" r="15" fill="white" fill-opacity="0.12" />
                            <circle cx="330" cy="90" r="7" fill="white" />
                        </g>
                        <g class="icon-float" style="animation-delay: -3.5s; transform-origin: 70px 302px;">
                            <line class="line-flow" x1="116" y1="272" x2="72" y2="300" stroke="white" stroke-opacity="0.45" stroke-width="2" style="animation-delay: -0.8s;" />
                            <circle cx="70" cy="302" r="15" fill="white" fill-opacity="0.12" />
                            <circle cx="70" cy="302" r="7" fill="white" />
                        </g>
                        <g class="icon-float" style="animation-delay: -5s; transform-origin: 330px 302px;">
                            <line class="line-flow" x1="284" y1="272" x2="328" y2="300" stroke="white" stroke-opacity="0.45" stroke-width="2" style="animation-delay: -1.1s;" />
                            <circle cx="330" cy="302" r="15" fill="white" fill-opacity="0.12" />
                            <circle cx="330" cy="302" r="7" fill="white" />
                        </g>

                        <path
                            d="M200 60 L300 100 L300 210 Q300 300 200 350 Q100 300 100 210 L100 100 Z"
                            fill="white"
                            fill-opacity="0.97"
                            stroke="white"
                            stroke-opacity="0.5"
                            stroke-width="2"
                        />

                        <path d="M180 195 V170 a20 20 0 0 1 40 0 V195" fill="none" stroke="#0369a1" stroke-width="10" stroke-linecap="round" />
                        <rect x="168" y="195" width="64" height="52" rx="10" fill="#0369a1" />
                        <circle cx="200" cy="215" r="6.5" fill="white" />
                        <rect x="196.5" y="219" width="7" height="13" rx="2" fill="white" />
                    </svg>
                </div>

                <p class="relative z-10 text-xs text-sky-100/70">Badan Siber dan Sandi Negara</p>

                {{-- Wavy divider — the signature shape from the reference,
                     recreated as a plain formulaic SVG wave (not traced),
                     layered on top of the seam so it's purely decorative
                     and can't affect layout even if it ever renders oddly. --}}
                <svg
                    class="pointer-events-none absolute -right-px top-0 h-full w-8"
                    viewBox="0 0 60 800"
                    preserveAspectRatio="none"
                    aria-hidden="true"
                >
                    <path
                        d="M0 0 L30 0 Q48 50 30 100 Q12 150 30 200 Q48 250 30 300 Q12 350 30 400 Q48 450 30 500 Q12 550 30 600 Q48 650 30 700 Q12 750 30 800 L0 800 Z"
                        fill="#4f46e5"
                    />
                </svg>
            </div>

            {{-- Right pane: swaps between the login form and the forgot-
                 password form via `mode` — both components are untouched
                 internally, this only controls which one is visible. --}}
            <div
                x-show="shown"
                x-transition:enter="transition ease-spring duration-700"
                x-transition:enter-start="opacity-0 translate-x-16"
                x-transition:enter-end="opacity-100 translate-x-0"
                :class="mode === 'signin' ? '' : 'lg:-translate-x-full'"
                class="relative flex w-full items-center justify-center overflow-y-auto p-6 transition-transform delay-[40ms] duration-[850ms] ease-spring lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2"
            >
                <div x-show="mode === 'signin'" x-transition:enter="transition ease-spring duration-500 delay-75" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.97]" x-transition:enter-end="opacity-100 translate-y-0 scale-100">
                    <livewire:auth.login />
                </div>
                <div x-show="mode === 'forgot'" x-cloak x-transition:enter="transition ease-spring duration-500 delay-75" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.97]" x-transition:enter-end="opacity-100 translate-y-0 scale-100">
                    <livewire:auth.forgot-password />
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
