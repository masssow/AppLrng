<?php

declare(strict_types=1);

namespace App\Application\IA\DTO;

use App\Application\IA\Enum\PromptType;

readonly class PromptDTO
{
    public function __construct(
        public PromptType $type,
        public string $version,
        public string $system,
        public string $user,
        public string $expectedFormat,
        public float $temperature,
        public array $metadata = [],
    ) {}
}
