<?php

namespace App\Support;

/**
 * Penulis PDF teks sederhana (A4, Helvetica) tanpa dependensi — dipakai
 * seeder data demo dan tes untuk membuat contoh SOP & bukti dukung yang
 * bisa dibaca pdfparser. Baris yang diawali "# " dicetak tebal.
 * Bukan untuk dokumen produksi.
 */
class SimplePdf
{
    private const WIDTH = 595;

    private const HEIGHT = 842;

    private const MARGIN = 60;

    private const FONT_SIZE = 11;

    private const LEADING = 15;

    private const CHARS_PER_LINE = 88;

    /** @param array<int, string> $pages Teks per halaman. */
    public static function make(array $pages): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $pageRefs = [];
        $next = 5;
        foreach (self::paginate($pages) as $lines) {
            $stream = self::contentStream($lines);
            $contentId = $next++;
            $pageId = $next++;
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::WIDTH . ' ' . self::HEIGHT . '] '
                . "/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentId} 0 R >>";
            $pageRefs[] = "{$pageId} 0 R";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $count = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }

        return $pdf . "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    /**
     * Bungkus teks menjadi baris, lalu pecah halaman yang terlalu panjang.
     *
     * @param  array<int, string>  $pages
     * @return array<int, array<int, array{text: string, bold: bool}>>
     */
    private static function paginate(array $pages): array
    {
        $perPage = (int) floor((self::HEIGHT - 2 * self::MARGIN) / self::LEADING);
        $result = [];

        foreach ($pages as $text) {
            $lines = [];
            foreach (preg_split("/\r?\n/", $text) as $paragraph) {
                $bold = str_starts_with($paragraph, '# ');
                $paragraph = $bold ? substr($paragraph, 2) : $paragraph;
                if (trim($paragraph) === '') {
                    $lines[] = ['text' => '', 'bold' => false];

                    continue;
                }
                foreach (explode("\n", wordwrap($paragraph, self::CHARS_PER_LINE, "\n", true)) as $wrapped) {
                    $lines[] = ['text' => $wrapped, 'bold' => $bold];
                }
            }
            foreach (array_chunk($lines, $perPage) as $chunk) {
                $result[] = $chunk;
            }
        }

        return $result ?: [[['text' => '', 'bold' => false]]];
    }

    /** @param array<int, array{text: string, bold: bool}> $lines */
    private static function contentStream(array $lines): string
    {
        $out = "BT\n" . self::LEADING . " TL\n" . self::MARGIN . ' ' . (self::HEIGHT - self::MARGIN) . " Td\n";
        foreach ($lines as $line) {
            $font = $line['bold'] ? 'F2' : 'F1';
            $text = self::escape($line['text']);
            $out .= "/{$font} " . self::FONT_SIZE . " Tf\n({$text}) Tj\nT*\n";
        }

        return $out . 'ET';
    }

    private static function escape(string $text): string
    {
        $text = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
