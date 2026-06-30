<?php

declare(strict_types=1);

namespace App\Application\IA\DTO;

readonly class ConsolidationDTO
{
    /**
     * @param QuestionDTO[] $questions
     */
    public function __construct(
        public array $questions,
        public ExerciceDTO $exercice,
        public string $niveauDifficulte,
        public array $conceptsCibles,
        public ?string $ressourceSuivanteSuggeree,
    ) {}
}
