<?php

declare(strict_types=1);

namespace App\Application\IA\Strategy;

class DefaultStrategy implements DomainPromptStrategyInterface
{
    public function supportsDomaine(string $key): bool
    {
        return true;
    }

    public function getSystemContext(): string
    {
        return 'Expert généraliste en pédagogie et apprentissage. S\'adapte au contexte fourni par l\'apprenant.';
    }

    public function getConsolidationGuidelines(): string
    {
        return 'Questions de compréhension active adaptées au contenu étudié. Exercice pratique directement applicable.';
    }

    public function getEvaluationGuidelines(): string
    {
        return 'Évalue la compréhension du concept et la capacité d\'application dans un contexte réel.';
    }

    public function getExerciseGuidelines(): string
    {
        return 'Exercice pratique réalisable avec les outils standards du domaine.';
    }

    public function getSuggestedTools(): array
    {
        return [];
    }
}
