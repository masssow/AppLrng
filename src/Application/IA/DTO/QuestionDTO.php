<?php

declare(strict_types=1);

namespace App\Application\IA\DTO;

readonly class QuestionDTO
{
    public function __construct(
        public string $texte,
        public string $type,
    ) {}
}
