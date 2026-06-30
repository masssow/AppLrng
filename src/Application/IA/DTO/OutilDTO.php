<?php

declare(strict_types=1);

namespace App\Application\IA\DTO;

readonly class OutilDTO
{
    public function __construct(
        public string $nom,
        public string $url,
        public string $instructions,
    ) {}
}
