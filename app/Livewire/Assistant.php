<?php

namespace App\Livewire;

use App\Services\Ai\AppAssistant;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Tombol "Asisten" di pojok kanan bawah setiap halaman (layouts/app).
 * Percakapan disimpan di sesi supaya tetap ada saat pengguna pindah halaman
 * lewat tautan jawaban. Isi jawabannya dari App\Services\Ai\AppAssistant.
 */
class Assistant extends Component
{
    /** Nama halaman yang sedang dibuka, sebagai konteks pertanyaan seperti "di halaman ini". */
    private const PAGES = [
        'dashboard' => 'Dashboard',
        'assets.index' => 'Manajemen Aset',
        'risks.index' => 'Manajemen Risiko',
        'changes.index' => 'Manajemen Perubahan',
        'data-information.index' => 'Manajemen Data & Informasi',
        'hr-risks.index' => 'Manajemen SDM',
        'knowledge.index' => 'Manajemen Pengetahuan',
        'services.index' => 'Manajemen Layanan',
        'security-programs.index' => 'Manajemen Keamanan Informasi',
        'evidence.index' => 'Evaluasi Bukti Dukung',
        'profile.edit' => 'Profil',
        'guide.index' => 'Panduan Penggunaan',
    ];

    private const SESSION_KEY = 'assistant.messages';

    public const EXAMPLES = [
        'Cara menambah aset baru',
        'Cari SOP permohonan server',
        'Berkas yang saya unggah nyambung ke mana?',
        'Dasar aturan manajemen risiko',
    ];

    #[Locked]
    public array $messages = [];

    #[Locked]
    public ?string $page = null;

    public function mount(): void
    {
        $this->messages = session(self::SESSION_KEY, []);
        $this->page = self::PAGES[request()->route()?->getName()] ?? null;
    }

    public function send(string $text = ''): void
    {
        $text = trim($text);
        if (mb_strlen($text) < 2) {
            return;
        }

        $key = 'assistant:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->messages[] = ['role' => 'user', 'text' => $text];
            $this->messages[] = ['role' => 'assistant', 'text' => 'Terlalu banyak pertanyaan. Coba lagi sebentar lagi.', 'links' => []];
            return;
        }
        RateLimiter::hit($key, 60);

        $history = [];
        $pending = null;
        foreach ($this->messages as $message) {
            if ($message['role'] === 'user') {
                $pending = $message['text'];
            } elseif ($pending !== null) {
                $history[] = ['q' => $pending, 'a' => $message['text']];
                $pending = null;
            }
        }

        $result = app(AppAssistant::class)->answer(mb_substr($text, 0, 500), $this->page, array_slice($history, -2));

        $this->messages[] = ['role' => 'user', 'text' => mb_substr($text, 0, 500)];
        $this->messages[] = ['role' => 'assistant', 'text' => $result['answer'], 'links' => $result['links'], 'quote' => $result['quote'] ?? null, 'ai' => $result['ai'] ?? null, 'draft' => $result['draft'] ?? null];
        $this->messages = array_slice($this->messages, -20);
        session([self::SESSION_KEY => $this->messages]);
    }

    public function clear(): void
    {
        $this->messages = [];
        session()->forget(self::SESSION_KEY);
    }

    public function render()
    {
        return view('livewire.assistant', ['examples' => self::EXAMPLES]);
    }
}
