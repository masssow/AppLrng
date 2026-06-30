<?php

declare(strict_types=1);

namespace App\Application\Dashboard\DTO;

final class ScoreAgregeDTO
{
    public function __construct(
        public readonly int $valeur,
        public readonly string $libelleQualitatif,
    ) {}
}
