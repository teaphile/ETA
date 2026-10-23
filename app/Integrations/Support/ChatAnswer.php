<?php

namespace App\Integrations\Support;

/**
 * A chatbot answer plus the evidence it was grounded in.
 *
 * `provider` records which adapter produced the answer so behaviour can be audited
 * and reported ("faq", "grounded_db", "openai", "fallback").
 */
final readonly class ChatAnswer
{
    /**
     * @param  array<int,array{title:string,url:string}>  $sources
     * @param  array<int,string>  $toolsUsed
     */
    public function __construct(
        public string $answer,
        public array $sources = [],
        public string $provider = 'fallback',
        public bool $grounded = false,
        public ?string $category = null,
        public array $toolsUsed = [],
        public ?string $limitation = null,
    ) {}

    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'sources' => $this->sources,
            'provider' => $this->provider,
            'grounded' => $this->grounded,
            'category' => $this->category,
            'tools_used' => $this->toolsUsed,
            'limitation' => $this->limitation,
        ];
    }
}
