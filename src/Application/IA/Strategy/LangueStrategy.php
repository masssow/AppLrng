<?php

declare(strict_types=1);

namespace App\Application\IA\Strategy;

class LangueStrategy implements DomainPromptStrategyInterface
{
    public function supportsDomaine(string $key): bool
    {
        return $key === 'langue';
    }

    public function getSystemContext(): string
    {
        return 'Expert en acquisition de langues étrangères et linguistique appliquée. Les exercices sont des productions écrites.';
    }

    public function getConsolidationGuidelines(): string
    {
        return 'Questions sur la grammaire, le vocabulaire et les structures en contexte. L\'exercice est une production écrite courte (3-5 phrases).';
    }

    public function getEvaluationGuidelines(): string
    {
        return 'Évalue la correction grammaticale, la pertinence du vocabulaire et la fluidité. Suggère des reformulations naturelles.';
    }

    public function getExerciseGuidelines(): string
    {
        return 'Exercice de production écrite avec un contexte communicatif réel. Peut être vérifié avec LanguageTool.';
    }

    public function getSuggestedTools(): array
    {
        return [
            ['nom' => 'LanguageTool', 'url' => 'https://languagetool.org'],
        ];
    }
}
