@props(['module'])

{{--
    Titik integrasi dalam satu baris (masukan pengguna 26 Nov 2026: "titik-titik
    integrasi harus dibuat lebih jelas bentuknya"). Kiri: modul asal data, tengah:
    modul ini, kanan: modul tujuan. Gelembung bisa diklik; penjelasannya muncul
    saat kursor diarahkan (title) dan lengkap di tombol Alur Data.
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
    {{-- Gelembung saja, tanpa kotak, judul, atau ikon (masukan 26 Nov 2026). --}}
    <div class="flex flex-wrap items-center gap-1.5 text-xs" data-tour="module-flow">
        @foreach ([$from, null, $to] as $items)
            @if ($items === null)
                @if ($from)
                    <span class="px-0.5 text-gray-400" aria-hidden="true">→</span>
                @endif
                <span class="rounded-full bg-brand-700 px-3 py-1 font-semibold text-white">{{ $self }}</span>
                @if ($to)
                    <span class="px-0.5 text-gray-400" aria-hidden="true">→</span>
                @endif
            @else
                @foreach ($items as $name => $item)
                    @php $tag = $item['url'] ? 'a' : 'span'; @endphp
                    <{{ $tag }} @if ($item['url']) href="{{ $item['url'] }}" @endif title="{{ implode(' ', array_unique($item['why'])) }}" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-gray-700 hover:border-brand-300 hover:text-brand-700">{{ $name }}</{{ $tag }}>
                @endforeach
            @endif
        @endforeach
    </div>
@endif
