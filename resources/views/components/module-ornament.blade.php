@props(['module'])

@php
    $palettes = [
        'sdm' => ['light' => '#cbd5e1', 'mid' => '#64748b', 'dark' => '#334155'],
        'pengetahuan' => ['light' => '#fcd34d', 'mid' => '#f59e0b', 'dark' => '#b45309'],
        'aset' => ['light' => '#5eead4', 'mid' => '#14b8a6', 'dark' => '#0f766e'],
        'keamanan' => ['light' => '#6ee7b7', 'mid' => '#10b981', 'dark' => '#047857'],
        'risiko' => ['light' => '#d8b4fe', 'mid' => '#a855f7', 'dark' => '#7e22ce'],
        'perubahan' => ['light' => '#a5b4fc', 'mid' => '#6366f1', 'dark' => '#4338ca'],
        'layanan' => ['light' => '#fda4af', 'mid' => '#f43f5e', 'dark' => '#be123c'],
        'data' => ['light' => '#7dd3fc', 'mid' => '#0ea5e9', 'dark' => '#0369a1'],
    ];
    $c = $palettes[$module] ?? null;
@endphp

@if ($c)
    <div class="ornament-reveal pointer-events-none absolute -right-6 -top-16 z-0 h-64 w-64 opacity-[0.24] sm:-right-8 sm:-top-20 sm:h-80 sm:w-80" style="--ornament-rotate: -6deg">
        <svg viewBox="0 0 340 340" class="h-full w-full" fill="none" xmlns="http://www.w3.org/2000/svg">

            @switch($module)
                @case('sdm')
                    {{-- A loose constellation of people, not a grid — one
                         "lead" node larger and forward, others scattered at
                         human, uneven distances like a real org chart drawn
                         by hand, not a repeating tile. --}}
                    <line x1="100" y1="120" x2="205" y2="82" stroke="{{ $c['mid'] }}" stroke-width="2.5" class="ornament-line-pulse" />
                    <line x1="100" y1="120" x2="145" y2="222" stroke="{{ $c['mid'] }}" stroke-width="2.5" class="ornament-line-pulse" />
                    <line x1="205" y1="82" x2="232" y2="178" stroke="{{ $c['mid'] }}" stroke-width="2.5" class="ornament-line-pulse" />
                    <circle cx="100" cy="120" r="40" fill="{{ $c['dark'] }}" />
                    <circle cx="205" cy="82" r="27" fill="{{ $c['mid'] }}" />
                    <circle cx="232" cy="178" r="23" fill="{{ $c['light'] }}" />
                    <circle cx="145" cy="222" r="21" fill="{{ $c['mid'] }}" />
                    @break

                @case('pengetahuan')
                    {{-- Open book, spine slightly off-center (asymmetric,
                         like it's genuinely lying open), plus a small
                         radiating "idea" mark tucked into its corner. --}}
                    <path d="M172 268 L172 130 Q172 108 130 100 L58 90 L58 240 L130 252 Q160 258 172 268 Z" fill="{{ $c['mid'] }}" />
                    <path d="M172 268 L172 130 Q172 108 214 100 L286 90 L286 240 L214 252 Q184 258 172 268 Z" fill="{{ $c['dark'] }}" />
                    <path d="M80 118 L130 126 M80 148 L130 156 M80 178 L128 186" stroke="{{ $c['light'] }}" stroke-width="2.5" stroke-linecap="round" />
                    <circle cx="252" cy="60" r="18" fill="{{ $c['light'] }}" class="ornament-line-pulse" />
                    <path d="M252 30v10M228 46l7 6M276 46l-7 6" stroke="{{ $c['light'] }}" stroke-width="3" stroke-linecap="round" class="ornament-line-pulse" />
                    @break

                @case('aset')
                    {{-- Server rack, mildly skewed for an isometric hint
                         rather than a flat frontal icon — three units of
                         decreasing width, status lights per unit. --}}
                    <g transform="skewY(-4)">
                        <rect x="70" y="70" width="220" height="56" rx="10" fill="{{ $c['dark'] }}" />
                        <rect x="90" y="146" width="190" height="56" rx="10" fill="{{ $c['mid'] }}" />
                        <rect x="110" y="222" width="160" height="56" rx="10" fill="{{ $c['light'] }}" />
                        <circle cx="260" cy="98" r="6" fill="{{ $c['light'] }}" />
                        <circle cx="240" cy="98" r="6" fill="{{ $c['light'] }}" opacity="0.6" />
                        <circle cx="256" cy="174" r="6" fill="{{ $c['dark'] }}" />
                        <circle cx="236" cy="174" r="6" fill="{{ $c['dark'] }}" opacity="0.6" />
                        <circle cx="248" cy="250" r="6" fill="{{ $c['mid'] }}" />
                    </g>
                    @break

                @case('keamanan')
                    {{-- Two nested shields (layered defense), a keyhole cut
                         into the front one so it reads as "locked", not
                         just a generic badge shape. --}}
                    <path d="M170 44 L266 78 V158 Q266 232 170 276 Q74 232 74 158 V78 Z" fill="{{ $c['light'] }}" opacity="0.55" />
                    <path d="M170 70 L240 96 V158 Q240 214 170 250 Q100 214 100 158 V96 Z" fill="{{ $c['mid'] }}" />
                    <circle cx="170" cy="148" r="20" fill="{{ $c['dark'] }}" />
                    <path d="M162 164 L178 164 L172 190 L168 190 Z" fill="{{ $c['dark'] }}" />
                    @break

                @case('risiko')
                    {{-- Radar: concentric rings plus a sweeping wedge — the
                         wedge is the one piece that actually rotates. --}}
                    <circle cx="170" cy="170" r="120" stroke="{{ $c['light'] }}" stroke-width="2" opacity="0.6" />
                    <circle cx="170" cy="170" r="82" stroke="{{ $c['mid'] }}" stroke-width="2" opacity="0.7" />
                    <circle cx="170" cy="170" r="44" stroke="{{ $c['dark'] }}" stroke-width="2" />
                    <g class="ornament-slow-spin" style="transform-origin: 170px 170px;">
                        <path d="M170 170 L170 50 A120 120 0 0 1 274 108 Z" fill="{{ $c['mid'] }}" opacity="0.45" />
                    </g>
                    <circle cx="170" cy="170" r="7" fill="{{ $c['dark'] }}" />
                    <circle cx="228" cy="120" r="6" fill="{{ $c['dark'] }}" class="ornament-line-pulse" />
                    @break

                @case('perubahan')
                    {{-- Two unequal arcs with arrowheads — deliberately
                         lopsided (not a perfect symmetric refresh icon) so
                         it reads as motion, not a static logo. --}}
                    <g class="ornament-slow-spin" style="transform-origin: 170px 170px;">
                        <path d="M240 100 A100 100 0 1 0 258 210" stroke="{{ $c['dark'] }}" stroke-width="16" stroke-linecap="round" fill="none" />
                        <path d="M232 72 L246 104 L214 112 Z" fill="{{ $c['dark'] }}" />
                        <path d="M100 240 A100 100 0 0 0 250 190" stroke="{{ $c['light'] }}" stroke-width="16" stroke-linecap="round" fill="none" opacity="0.7" />
                        <path d="M108 268 L94 236 L126 228 Z" fill="{{ $c['light'] }}" opacity="0.7" />
                    </g>
                    @break

                @case('layanan')
                    {{-- Service mesh: one hub, five leaf nodes at uneven
                         distances, thin connecting lines. --}}
                    <line x1="180" y1="160" x2="90" y2="90" stroke="{{ $c['mid'] }}" stroke-width="2" class="ornament-line-pulse" />
                    <line x1="180" y1="160" x2="260" y2="80" stroke="{{ $c['mid'] }}" stroke-width="2" class="ornament-line-pulse" />
                    <line x1="180" y1="160" x2="270" y2="190" stroke="{{ $c['mid'] }}" stroke-width="2" class="ornament-line-pulse" />
                    <line x1="180" y1="160" x2="210" y2="270" stroke="{{ $c['mid'] }}" stroke-width="2" class="ornament-line-pulse" />
                    <line x1="180" y1="160" x2="100" y2="230" stroke="{{ $c['mid'] }}" stroke-width="2" class="ornament-line-pulse" />
                    <circle cx="180" cy="160" r="30" fill="{{ $c['dark'] }}" />
                    <circle cx="90" cy="90" r="14" fill="{{ $c['light'] }}" />
                    <circle cx="260" cy="80" r="17" fill="{{ $c['mid'] }}" />
                    <circle cx="270" cy="190" r="12" fill="{{ $c['light'] }}" />
                    <circle cx="210" cy="270" r="16" fill="{{ $c['mid'] }}" />
                    <circle cx="100" cy="230" r="13" fill="{{ $c['light'] }}" />
                    @break

                @case('data')
                    {{-- Stacked database cylinders with data trailing off
                         to one side, rather than sitting dead-center. --}}
                    <ellipse cx="140" cy="86" rx="86" ry="26" fill="{{ $c['dark'] }}" />
                    <path d="M54 86 V140 Q54 166 140 166 Q226 166 226 140 V86" fill="{{ $c['dark'] }}" opacity="0.85" />
                    <ellipse cx="140" cy="140" rx="86" ry="26" fill="{{ $c['mid'] }}" />
                    <path d="M54 140 V194 Q54 220 140 220 Q226 220 226 194 V140" fill="{{ $c['mid'] }}" opacity="0.85" />
                    <ellipse cx="140" cy="194" rx="86" ry="26" fill="{{ $c['light'] }}" />
                    <path d="M226 110 Q270 118 288 100 M226 150 Q276 162 300 150 M226 190 Q268 206 292 202" stroke="{{ $c['light'] }}" stroke-width="3" stroke-linecap="round" fill="none" class="ornament-drift" />
                    @break
            @endswitch
        </svg>
    </div>
@endif
