<?php

namespace App\Services\Ai;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\XObject\Image;
use Throwable;

/**
 * Membaca PDF: teks per halaman, jumlah gambar per halaman, dan tanda
 * tangan digital (PAdES) yang tertanam di berkas.
 *
 * Batasan yang disengaja: cap basah dan tanda tangan basah hasil pindai
 * hanyalah gambar — isinya tidak bisa dibaca dari teks. Yang bisa
 * dideteksi hanya "ada gambar di halaman pengesahan", sebagai petunjuk
 * untuk dicek mata manusia.
 */
class PdfReader
{
    /**
     * @return array{
     *   ok: bool, error: ?string, page_count: int,
     *   pages: array<int, string>, images_per_page: array<int, int>,
     *   digital_signature: bool, text_length: int
     * }
     */
    public function read(string $absolutePath): array
    {
        $result = [
            'ok' => false,
            'error' => null,
            'page_count' => 0,
            'pages' => [],
            'images_per_page' => [],
            'digital_signature' => false,
            'text_length' => 0,
        ];

        try {
            $raw = (string) file_get_contents($absolutePath);
            $result['digital_signature'] = self::hasDigitalSignature($raw);

            $config = new Config();
            $config->setRetainImageContent(false);
            $config->setDecodeMemoryLimit(64 * 1024 * 1024);

            $document = (new Parser([], $config))->parseContent($raw);

            foreach ($document->getPages() as $index => $page) {
                $text = self::normalize($page->getText());
                $result['pages'][$index + 1] = $text;
                $result['images_per_page'][$index + 1] = collect($page->getXObjects())
                    ->filter(fn ($object) => $object instanceof Image)
                    ->count();
            }

            $result['page_count'] = count($result['pages']);
            $result['text_length'] = array_sum(array_map('mb_strlen', $result['pages']));
            $result['ok'] = true;
        } catch (Throwable $e) {
            $result['error'] = 'PDF tidak dapat dibaca: ' . mb_substr($e->getMessage(), 0, 160);
        }

        return $result;
    }

    /** Teks seluruh halaman, dipisah penanda halaman. */
    public static function fullText(array $pages): string
    {
        return collect($pages)->map(fn ($text, $no) => "[Hal. {$no}]\n{$text}")->implode("\n\n");
    }

    /**
     * Tanda tangan elektronik tersertifikasi (mis. BSrE) disimpan sebagai
     * dictionary /Sig dengan /ByteRange di dalam berkas PDF.
     */
    public static function hasDigitalSignature(string $raw): bool
    {
        return (bool) preg_match('#/Type\s*/Sig\b|/ByteRange\s*\[|/SubFilter\s*/(adbe\.pkcs7|ETSI\.CAdES)#', $raw);
    }

    public static function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\t", "\u{00A0}"], ["\n", "\n", ' ', ' '], $text);
        $text = preg_replace('/[ ]{2,}/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
