<?php

declare(strict_types=1);

namespace App\Application\IA\Strategy;

class DesignStrategy implements DomainPromptStrategyInterface
{
    public function supportsDomaine(string $key): bool
    {
        return $key === 'design';
    }

    public function getSystemContext(): string
    {
        return 'Expert en design graphique, UX/UI et créativité visuelle. Les exercices sont des briefs créatifs à réaliser.';
    }

    public function getConsolidationGuidelines(): string
    {
        return 'Questions sur les principes de design, la hiérarchie visuelle, la typographie et la couleur. L\'exercice est un brief créatif court.';
    }

    public function getEvaluationGuidelines(): string
    {
        return 'Évalue la compréhension des principes de design et la justification des choix créatifs.';
    }

    public function getExerciseGuidelines(): string
    {
        return 'L\'exercice doit être réalisable dans Figma ou Canva. Donne un brief précis avec contraintes claires.';
    }

    public function getSuggestedTools(): array
    {
        return [
            ['nom' => 'Figma', 'url' => 'https://figma.com'],
            ['nom' => 'Canva', 'url' => 'https://canva.com'],
        ];
    }
}
