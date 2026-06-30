<?php

declare(strict_types=1);

namespace App\Application\IA\DTO;

readonly class EvaluationDTO
{
    public function __construct(
        public int $score,
        public string $decision,
        public string $feedback,
        public string $pointFort,
        public string $pointAmelioration,
        public string $encouragement,
    ) {}
}
