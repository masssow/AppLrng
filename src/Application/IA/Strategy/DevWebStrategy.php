<?php

declare(strict_types=1);

namespace App\Application\IA\Strategy;

class DevWebStrategy implements DomainPromptStrategyInterface
{
    public function supportsDomaine(string $key): bool
    {
        return $key === 'dev_web';
    }

    public function getSystemContext(): string
    {
        return 'Expert en développement web (HTML, CSS, JavaScript, PHP, frameworks modernes). Les exercices sont du code à écrire et tester.';
    }

    public function getConsolidationGuidelines(): string
    {
        return 'Génère des questions qui testent la compréhension du code, pas la mémorisation de syntaxe. L\'exercice doit être un mini-défi de code réaliste.';
    }

    public function getEvaluationGuidelines(): string
    {
        return 'Évalue la logique et l\'approche, pas la syntaxe exacte. Un code qui fonctionne différemment mais correctement est valide.';
    }

    public function getExerciseGuidelines(): string
    {
        return 'L\'exercice doit pouvoir être réalisé dans un éditeur en ligne (CodeSandbox, Replit, StackBlitz). Fournis un contexte de départ clair.';
    }

    public function getSuggestedTools(): array
    {
        return [
            ['nom' => 'CodeSandbox', 'url' => 'https://codesandbox.io'],
            ['nom' => 'Replit', 'url' => 'https://replit.com'],
        ];
    }
}
