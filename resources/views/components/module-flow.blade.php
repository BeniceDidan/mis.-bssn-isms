@props(['module'])

{{--
    Titik integrasi dalam satu baris (masukan pengguna 26 Nov 2026: "titik-titik
    integrasi harus dibuat lebih jelas bentuknya"). Kiri: modul asal data, tengah:
    modul ini, kanan: modul tujuan. Kotak bisa diklik; tanda petir = otomatis.
    Isinya dari App\Support\IntegrationMap, sama dengan tombol Alur Data dan Asisten.
--}}
@php
    $map = \App\Support\IntegrationMap::for($module);
    $pages = [
        'SDM' => route('hr-risks.index'),
        'Pengetahuan' => route('knowledge.index'),
        'Aset' => route('assets.index'),
        'Keamanan Informasi' => route('security-programs.index'),
        'Risiko' => route('risks.index'),
        'Perubahan' => route('changes.index'),
        'Layanan' => route('services.index'),
        'Data & Informasi' => route('data-information.index'),
        'Tanya Dokumen' => route('knowledge.index', ['tab' => 'tanya']),
        'Analisis tiket' => route('services.index', ['tab' => 'helpdesk']),
        'Excel' => null,
    ];
    $alias = ['Impor Excel' => 'Excel', 'Analisis AI tiket' => 'Analisis tiket'];
    $side = function (string $dir) use ($map, $pages, $alias) {
        $out = [];
        foreach ($map['flows'] as $flow) {
            if ($flow['dir'] !== $dir) {
                continue;
            }
            $names = preg_split('/,\s*|\s+dan\s+/u', preg_replace('/\s*\(.*?\)/u', '', $flow['to']));
            foreach ($names as $name) {
                $name = trim(preg_replace('/^Manajemen\s+/u', '', trim($name)));
                $name = $alias[$name] ?? $name;
                if (! array_key_exists($name, $pages)) {
                    continue;
                }
                $out[$name] ??= ['url' => $pages[$name], 'auto' => false, 'why' => []];
                $out[$name]['auto'] = $out[$name]['auto'] || $flow['type'] === 'auto';
                $out[$name]['why'][] = $flow['text'];
            }
        }

        return $out;
    };
    $from = $map ? $side('in') : [];
    $to = $map ? $side('out') : [];
    $self = $map ? preg_replace('/^Manajemen\s+/u', '', $map['label']) : '';
@endphp

@if ($map && ($from || $to))
    <div class="glass-panel grid gap-3 rounded-2xl px-4 py-3 text-sm lg:grid-cols-[1fr_auto_1fr] lg:items-center lg:gap-4" data-tour="module-flow">
        @foreach ([['Datang dari', $from], [null, null], ['Dikirim ke', $to]] as [$title, $items])
            @if ($title === null)
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-right class="hidden h-4 w-4 text-gray-400 lg:block" />
                    <span class="rounded-full bg-brand-700 px-3 py-1 text-xs font-semibold text-white">{{ $self }}</span>
                    <x-heroicon-o-arrow-right class="hidden h-4 w-4 text-gray-400 lg:block" />
                </div>
            @else
                <div class="min-w-0">
                    <p class="mb-1.5 flex items-center gap-2 text-xs text-gray-500 {{ $title === 'Datang dari' ? 'lg:justify-end' : '' }}">
                        {{ $title }}
                        @if ($title === 'Dikirim ke')
                            <span class="inline-flex items-center gap-0.5 text-[11px] text-gray-400"><x-heroicon-s-bolt class="h-3 w-3 text-amber-500" />= otomatis</span>
                        @endif
                    </p>
                    <div class="flex flex-wrap gap-1.5 {{ $title === 'Datang dari' ? 'lg:justify-end' : '' }}">
                    @forelse ($items as $name => $item)
                        @php
                            $tip = implode(' ', array_unique($item['why']));
                            $tag = $item['url'] ? 'a' : 'span';
                        @endphp
                        <{{ $tag }} @if ($item['url']) href="{{ $item['url'] }}" @endif title="{{ $tip }}" class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs text-gray-700 hover:border-brand-300 hover:text-brand-700">
                            @if ($item['auto'])
                                <x-heroicon-s-bolt class="h-3 w-3 text-amber-500" />
                            @endif
                            {{ $name }}
                        </{{ $tag }}>
                    @empty
                        <span class="text-xs text-gray-400">Tidak ada</span>
                    @endforelse
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif
