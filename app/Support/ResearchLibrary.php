<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Isi folder docs/riset (riset internet selama pengembangan) dalam bentuk
 * yang bisa dicari Asisten:
 *  - sources: baris tabel sumber berkode (R1, K1, H1, P1, S1): judul, status, tautan;
 *  - units: potongan bermakna (baris tabel, butir daftar, paragraf) lengkap
 *    dengan judul bagiannya. Kode sumber di teks diganti nama pendeknya
 *    ("P2" → "PermenPANRB 5/2020") supaya terbaca tanpa membuka berkasnya.
 *
 * Berkas dianggap draf selama judulnya memuat "DRAF" atau "BELUM DITINJAU".
 * Setelah ditinjau, ganti judulnya menjadi "... — DITINJAU" dan label draf
 * di Asisten hilang sendiri untuk berkas itu.
 *
 * Hanya README.md dan berkas bernomor (01-…md) yang dibaca;
 * referensi-simantik.md milik proyek lain yang sudah dihentikan.
 */
class ResearchLibrary
{
    public const STATUS_LABELS = ['✅' => 'Sesuai', '🟡' => 'Sesuai sebagian', '🔶' => 'Perlu penyesuaian', '⚪' => 'Belum ada rujukan resmi'];

    /** @var array<string, array>|null */
    private static ?array $loaded = null;

    private static ?string $loadedKey = null;

    /**
     * @return array{files: array<string, array{title: string, draft: bool}>, sources: array<int, array>, units: array<int, array>}
     */
    public static function load(?string $dir = null): array
    {
        $dir ??= config('ai.research_path') ?: base_path('docs/riset');
        $paths = glob($dir . '/[0-9][0-9]-*.md') ?: [];
        sort($paths);
        if (is_file($dir . '/README.md')) {
            array_unshift($paths, $dir . '/README.md');
        }

        $key = md5(implode('|', array_map(fn ($p) => $p . '@' . filemtime($p) . '@' . filesize($p), $paths)));
        if (self::$loadedKey !== $key) {
            self::$loaded = self::parse($paths);
            self::$loadedKey = $key;
        }

        return self::$loaded;
    }

    /** Untuk tes. */
    public static function forget(): void
    {
        self::$loaded = null;
        self::$loadedKey = null;
    }

    private static function parse(array $paths): array
    {
        $files = [];
        $sources = [];
        $units = [];

        foreach ($paths as $path) {
            $file = basename($path);
            $state = ['file' => $file, 'h2' => null, 'h3' => null];
            $table = null;
            $block = null; // ['kind' => bullet|para, 'lines' => []]

            // Bagian untuk peninjau (pertanyaan terbuka, cara meninjau, daftar berkas) bukan jawaban.
            $state['skip'] = false;
            $flushBlock = function () use (&$block, &$units, &$state) {
                $text = $block === null ? '' : self::clean(implode(' ', $block['lines']));
                if ($block !== null && $block['kind'] === 'para' && preg_match('/^pertanyaan untuk peninjau:?$/iu', $text)) {
                    $state['skip'] = true;
                } elseif ($text !== '' && ! $state['skip']) {
                    $units[] = self::unit($state, $block['kind'], $text, implode(' ', $block['lines']));
                }
                $block = null;
            };
            $flushTable = function () use (&$table, &$units, &$sources, &$state) {
                if ($table === null) {
                    return;
                }
                [$header, $rows] = [$table['header'], $table['rows']];
                $table = null;
                $first = self::clean($header[0] ?? '');

                if ($first === 'Kode') {
                    $titleCol = self::clean($header[1] ?? 'Sumber');
                    $statusAt = array_search('Status', array_map([self::class, 'clean'], $header), true);
                    $linkAt = array_search('Tautan', array_map([self::class, 'clean'], $header), true);
                    $noteAt = array_search('Isi yang dipakai', array_map([self::class, 'clean'], $header), true);
                    foreach ($rows as $cells) {
                        $id = self::clean($cells[0] ?? '');
                        if (! preg_match('/^[A-Z]\d{1,2}$/', $id)) {
                            continue;
                        }
                        $title = self::clean($cells[1] ?? '');
                        preg_match_all('~https?://[^\s|)]+~u', $linkAt !== false ? ($cells[$linkAt] ?? '') : implode(' ', $cells), $urls);
                        $source = [
                            'id' => $id,
                            'file' => $state['file'],
                            'category' => $titleCol,
                            'title' => $title,
                            'short' => self::shortName($title),
                            'status' => $statusAt !== false ? (self::clean($cells[$statusAt] ?? '') ?: null) : null,
                            'note' => $noteAt !== false ? (self::clean($cells[$noteAt] ?? '') ?: null) : null,
                            'url' => rtrim($urls[0][0] ?? '', '.,') ?: null,
                        ];
                        $sources[] = $source;
                        $text = $title . ($source['status'] ? "\nStatus: {$source['status']}" : '') . ($source['note'] ? "\nIsi yang dipakai: {$source['note']}" : '');
                        $units[] = self::unit($state, 'source', $text, implode(' | ', $cells)) + ['source' => $id];
                    }

                    return;
                }
                // Legenda status, matriks (header "Dampak \ Urgensi"), dan daftar berkas README tidak dijadikan jawaban.
                if ($first === 'Status' || $first === 'Berkas' || str_contains($first, '\\') || $state['skip']) {
                    return;
                }
                foreach ($rows as $cells) {
                    $lines = [self::clean($cells[0] ?? '')];
                    foreach (array_slice($cells, 1, null, true) as $i => $cell) {
                        $value = self::clean($cell);
                        if ($value === '' || $value === '—') {
                            continue;
                        }
                        $name = self::clean($header[$i] ?? '');
                        $value = self::STATUS_LABELS[$value] ?? $value;
                        $lines[] = ($name !== '' ? "{$name}: " : '') . $value;
                    }
                    $units[] = self::unit($state, 'row', implode("\n", $lines), implode(' | ', $cells));
                }
            };

            foreach (preg_split('/\R/u', (string) file_get_contents($path)) as $line) {
                if (preg_match('/^# (.+)$/u', $line, $m)) {
                    $files[$file] = [
                        'title' => trim(preg_replace(['/\s+—\s+.*$/u', '/^\d+\s*·\s*/u'], '', $m[1]) ?? $m[1]),
                        'draft' => (bool) preg_match('/\bDRAF\b|BELUM DITINJAU/iu', $m[1]),
                    ];
                    $state['fileTitle'] = $files[$file]['title'];
                    continue;
                }
                if (preg_match('/^(#{2,3}) (.+)$/u', $line, $m)) {
                    $flushBlock();
                    $flushTable();
                    $heading = self::clean(preg_replace('/^\d+\.\s*/u', '', $m[2]) ?? $m[2]);
                    $state['skip'] = (bool) preg_match('/pertanyaan untuk peninjau|cara meninjau/iu', $heading);
                    if ($m[1] === '##') {
                        [$state['h2'], $state['h3']] = [$heading, null];
                    } else {
                        $state['h3'] = $heading;
                    }
                    continue;
                }
                if (str_starts_with(ltrim($line), '|')) {
                    $flushBlock();
                    $cells = array_map('trim', explode('|', trim(trim($line), '|')));
                    if (preg_match('/^\|?\s*:?-{3,}/u', trim($line))) {
                        continue; // garis pemisah |---|
                    }
                    if ($table === null) {
                        $table = ['header' => $cells, 'rows' => []];
                    } else {
                        $table['rows'][] = $cells;
                    }
                    continue;
                }
                $flushTable();

                if (trim($line) === '' || trim($line) === '---') {
                    $flushBlock();
                    continue;
                }
                $text = preg_replace('/^>\s?/u', '', $line) ?? $line;
                if (preg_match('/^\s*(?:[-*]|\d+\.)\s+(.+)$/u', $text, $m)) {
                    $flushBlock();
                    $block = ['kind' => 'bullet', 'lines' => [$m[1]]];
                    continue;
                }
                if ($block === null) {
                    $block = ['kind' => 'para', 'lines' => []];
                }
                $block['lines'][] = trim($text);
            }
            $flushBlock();
            $flushTable();
        }

        // Kode sumber di teks → nama pendek, per berkas (P1 di berkas 04 ≠ prioritas P1 di berkas 03).
        $byFile = [];
        foreach ($sources as $source) {
            $byFile[$source['file']][$source['id']] = $source;
        }
        // Judul sumber yang merujuk sumber lain ("cocok dengan R1") ikut diganti.
        foreach ($sources as &$source) {
            $known = $byFile[$source['file']] ?? [];
            $source['title'] = preg_replace_callback(
                '/(?<![\w–\-\/])([A-Z]\d{1,2})(?![\w–\-])/u',
                fn ($x) => isset($known[$x[1]]) && $x[1] !== $source['id'] ? $known[$x[1]]['short'] : $x[1],
                $source['title']
            ) ?? $source['title'];
        }
        unset($source);

        foreach ($units as &$unit) {
            $known = $byFile[$unit['file']] ?? [];
            preg_match_all('/(?<![\w–\-\/])([A-Z]\d{1,2})(?![\w–\-])/u', $unit['raw'], $m);
            $unit['refs'] = array_values(array_unique(array_filter($m[1], fn ($id) => isset($known[$id]))));
            if (($unit['source'] ?? null) !== null) {
                $unit['refs'] = [$unit['source']];
            }
            foreach (['text', 'section', 'subsection'] as $field) {
                $unit[$field] = $unit[$field] === null ? null : (preg_replace_callback(
                    '/(?<![\w–\-\/])([A-Z]\d{1,2})(?![\w–\-])/u',
                    fn ($x) => isset($known[$x[1]]) ? $known[$x[1]]['short'] : $x[1],
                    $unit[$field]
                ) ?? $unit[$field]);
            }
            $unit['draft'] = $files[$unit['file']]['draft'] ?? true;
            $unit['fileTitle'] = $files[$unit['file']]['title'] ?? $unit['file'];
            unset($unit['raw']);
        }
        unset($unit);

        return ['files' => $files, 'sources' => $sources, 'units' => array_values($units)];
    }

    private static function unit(array $state, string $kind, string $text, string $raw): array
    {
        return [
            'file' => $state['file'],
            'section' => $state['h2'],
            'subsection' => $state['h3'],
            'kind' => $kind,
            'text' => $text,
            'raw' => $raw,
        ];
    }

    /** Markdown → teks biasa. */
    public static function clean(string $text): string
    {
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/u', '$1', $text) ?? $text; // [teks](tautan)
        $text = str_replace(['**', '__', '`'], '', $text);
        $text = preg_replace('/(?<![\w*])\*(?!\s)([^*]+?)(?<!\s)\*(?![\w*])/u', '$1', $text) ?? $text; // *miring*

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** "Perpres 95/2018 tentang SPBE (…)" → "Perpres 95/2018". */
    public static function shortName(string $title): string
    {
        // Rujukan ilmiah "Chen dkk. (2024), Judul…" → "Chen dkk. (2024)".
        if (preg_match('/^[^,(]+\(\d{4}\)/u', $title, $m)) {
            return trim($m[0]);
        }
        $cut = mb_strlen($title);
        foreach ([' tentang ', ' — ', ', ', ' ('] as $delimiter) {
            $at = mb_strpos($title, $delimiter);
            if ($at !== false && $at > 0 && $at < $cut) {
                $cut = $at;
            }
        }

        return trim(mb_substr($title, 0, $cut));
    }

    /**
     * Sumber unik: peraturan yang sama bisa tercantum di beberapa berkas
     * (mis. UU 27/2022 di berkas 02 dan 04). Yang punya status didahulukan.
     */
    public static function uniqueSources(array $sources): array
    {
        usort($sources, fn ($a, $b) => ($b['status'] !== null) <=> ($a['status'] !== null));

        return collect($sources)->unique(fn ($s) => Str::lower($s['short']))->values()->all();
    }
}
