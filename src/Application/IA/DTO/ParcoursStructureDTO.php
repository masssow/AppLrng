<?php

declare(strict_types=1);

namespace App\Application\IA\DTO;

readonly class ParcoursStructureDTO
{
    public function __construct(
        public array $themes,
        public array $ordreSuggere,
        public array $prerequisDetectes,
        public array $risquesApprentissage,
        public array $projetFilRouge,
        public ?string $suggestionRessourceManquante,
    ) {}
}
