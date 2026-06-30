<?php

declare(strict_types=1);

namespace App\Application\IA\DTO;

readonly class RawAIResponse
{
    public function __construct(
        public string $content,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
    ) {}
}
