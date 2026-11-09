<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Klien tipis ke Ollama yang berjalan di server sendiri.
 *
 * Semua metode mengembalikan null bila model tidak tersedia/gagal, sehingga
 * pemanggil wajib punya jalur cadangan (mesin aturan) — aplikasi tidak
 * boleh berhenti hanya karena AI mati.
 */
class LocalLlm
{
    public function enabled(): bool
    {
        return config('ai.provider') === 'ollama';
    }

    /** Daftar model yang terpasang di sebuah server Ollama (di-cache 60 detik). */
    public function installedModels(?string $baseUrl = null): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $baseUrl = rtrim($baseUrl ?? (string) config('ai.ollama_url'), '/');

        return Cache::remember('ai.ollama.models.' . md5($baseUrl), 60, function () use ($baseUrl) {
            try {
                $resp = Http::timeout(3)->get($baseUrl . '/api/tags');

                return $resp->successful()
                    ? collect($resp->json('models', []))->pluck('name')->map(fn ($n) => strtok($n, ':'))->unique()->values()->all()
                    : [];
            } catch (Throwable) {
                return [];
            }
        });
    }

    public function chatAvailable(): bool
    {
        return in_array($this->baseName(config('ai.chat_model')), $this->installedModels(config('ai.ollama_url')), true);
    }

    public function embedAvailable(): bool
    {
        return in_array($this->baseName(config('ai.embed_model')), $this->installedModels(config('ai.embed_url')), true);
    }

    public function chatEngineName(): string
    {
        return 'LLM lokal (' . config('ai.chat_model') . ')';
    }

    /**
     * Satu kali generate. $json = true memaksa keluaran JSON valid (mode
     * format Ollama); hasilnya sudah di-decode menjadi array.
     */
    public function generate(string $prompt, bool $json = false, ?string $system = null): string|array|null
    {
        if (! $this->chatAvailable()) {
            return null;
        }

        try {
            $resp = Http::timeout(config('ai.timeout'))->post($this->url('/api/generate'), array_filter([
                'model' => config('ai.chat_model'),
                'system' => $system,
                'prompt' => $prompt,
                'stream' => false,
                'format' => $json ? 'json' : null,
                'options' => ['temperature' => 0.2, 'num_ctx' => 8192],
            ], fn ($v) => $v !== null));

            if (! $resp->successful()) {
                return null;
            }

            $text = trim((string) $resp->json('response'));

            if (! $json) {
                return $text !== '' ? $text : null;
            }

            $decoded = json_decode($text, true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            $this->forgetModels(config('ai.ollama_url'));

            return null;
        }
    }

    /**
     * Embedding untuk beberapa teks sekaligus.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>|null
     */
    public function embed(array $texts): ?array
    {
        if ($texts === [] || ! $this->embedAvailable()) {
            return null;
        }

        try {
            $resp = Http::timeout(config('ai.timeout'))->post(rtrim((string) config('ai.embed_url'), '/') . '/api/embed', [
                'model' => config('ai.embed_model'),
                'input' => array_values($texts),
            ]);

            $vectors = $resp->successful() ? $resp->json('embeddings') : null;

            return is_array($vectors) && count($vectors) === count($texts) ? $vectors : null;
        } catch (Throwable) {
            $this->forgetModels(config('ai.embed_url'));

            return null;
        }
    }

    private function forgetModels(?string $baseUrl): void
    {
        Cache::forget('ai.ollama.models.' . md5(rtrim((string) $baseUrl, '/')));
    }

    /** @param array<int, float> $a @param array<int, float> $b */
    public static function cosine(array $a, array $b): float
    {
        $dot = $na = $nb = 0.0;
        $n = min(count($a), count($b));

        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }

        return ($na > 0 && $nb > 0) ? $dot / (sqrt($na) * sqrt($nb)) : 0.0;
    }

    private function baseName(?string $model): string
    {
        return strtok((string) $model, ':') ?: '';
    }

    private function url(string $path): string
    {
        return rtrim((string) config('ai.ollama_url'), '/') . $path;
    }
}
