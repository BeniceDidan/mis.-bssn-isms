<?php

/*
|--------------------------------------------------------------------------
| Asisten AI
|--------------------------------------------------------------------------
| Model dijalankan on-premise lewat Ollama supaya isi dokumen OPD, tiket,
| dan kebijakan internal tidak dikirim ke layanan pihak ketiga. Kalau
| Ollama mati atau provider = none, setiap fitur tetap jalan memakai mesin
| aturan (deterministik) — hasil AI selalu draf yang diputuskan manusia.
*/

return [

    'provider' => env('AI_PROVIDER', 'ollama'), // ollama | none

    'ollama_url' => env('AI_OLLAMA_URL', 'http://127.0.0.1:11434'),

    // Model teks untuk ringkasan, penilaian, triase, dan draf balasan.
    'chat_model' => env('AI_CHAT_MODEL', 'llama3.2'),

    // Model embedding untuk pencarian semantik. bge-m3 multibahasa,
    // jauh lebih paham Bahasa Indonesia daripada nomic-embed-text.
    'embed_model' => env('AI_EMBED_MODEL', 'bge-m3'),

    // Server Ollama untuk embedding bila berbeda dari server teks (mis. PC
    // dengan dua Ollama: WSL di 11434 dan Windows di port lain).
    'embed_url' => env('AI_EMBED_URL', env('AI_OLLAMA_URL', 'http://127.0.0.1:11434')),

    'timeout' => (int) env('AI_TIMEOUT', 120),

    // Batas teks dokumen yang dikirim ke model per permintaan (karakter).
    'max_prompt_chars' => (int) env('AI_MAX_PROMPT_CHARS', 6000),

    'chunk_chars' => 900,
    'chunk_overlap' => 150,
];
