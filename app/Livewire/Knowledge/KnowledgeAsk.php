<?php

namespace App\Livewire\Knowledge;

use App\Models\KnowledgeAsset;
use App\Services\Ai\KnowledgeSearch;
use Livewire\Component;

class KnowledgeAsk extends Component
{
    public string $question = '';

    public ?array $result = null;

    public ?string $askedQuestion = null;

    public function ask(KnowledgeSearch $search): void
    {
        $this->validate(['question' => ['required', 'string', 'min:4', 'max:300']], [
            'question.required' => 'Tulis pertanyaannya dulu.',
            'question.min' => 'Pertanyaan terlalu pendek.',
        ]);

        $this->askedQuestion = trim($this->question);
        $this->result = $search->ask($this->askedQuestion);
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-ask', [
            'indexedCount' => KnowledgeAsset::whereIn('index_status', ['terindeks', 'kata_kunci'])->count(),
            'pendingCount' => KnowledgeAsset::where('index_status', 'menunggu')->count(),
        ]);
    }
}
