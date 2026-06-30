<?php

declare(strict_types=1);

namespace App\Application\IA\Strategy;

class ComptabiliteStrategy implements DomainPromptStrategyInterface
{
    public function supportsDomaine(string $key): bool
    {
        return $key === 'comptabilite';
    }

    public function getSystemContext(): string
    {
        return 'Expert en comptabilité, gestion financière et fiscalité. Les exercices sont des cas pratiques chiffrés.';
    }

    public function getConsolidationGuidelines(): string
    {
        return 'Questions sur les principes comptables, les mécanismes de débit/crédit et les règles fiscales. L\'exercice est un cas chiffré à résoudre.';
    }

    public function getEvaluationGuidelines(): string
    {
        return 'Évalue la logique comptable, la justification des écritures et l\'exactitude des calculs. Explique les erreurs de principe.';
    }

    public function getExerciseGuidelines(): string
    {
        return 'Exercice de saisie comptable ou de calcul financier réalisable dans Google Sheets ou Excel.';
    }

    public function getSuggestedTools(): array
    {
        return [
            ['nom' => 'Google Sheets', 'url' => 'https://sheets.google.com'],
        ];
    }
}
