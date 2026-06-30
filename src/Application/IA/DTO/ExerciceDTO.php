<?php

declare(strict_types=1);

namespace App\Application\IA\DTO;

readonly class ExerciceDTO
{
    public function __construct(
        public string $enonce,
        public ?OutilDTO $outilSuggere,
        public array $criteresReussite,
    ) {}
}
