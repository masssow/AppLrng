<?php

declare(strict_types=1);

namespace App\Infrastructure\IA\Gateway;

use App\Application\IA\DTO\PromptDTO;
use App\Application\IA\DTO\RawAIResponse;
use App\Application\IA\Enum\PromptType;
use App\Application\IA\Port\IAGatewayInterface;

/** Zéro appel API — utilisé dans tous les tests */
class MockIAGateway implements IAGatewayInterface
{
    public function complete(PromptDTO $prompt): RawAIResponse
    {
        return match ($prompt->type) {
            PromptType::CONSOLIDATION => new RawAIResponse(json_encode([
                'questions' => [
                    ['texte' => 'Question test 1', 'type' => 'comprehension'],
                    ['texte' => 'Question test 2', 'type' => 'application'],
                    ['texte' => 'Question test 3', 'type' => 'analyse'],
                ],
                'exercice' => [
                    'enonce' => 'Exercice test',
                    'outil_suggere' => null,
                    'criteres_reussite' => ['Critère 1'],
                ],
                'niveau_difficulte' => 'moyen',
                'concepts_cibles' => ['concept_test'],
                'ressource_suivante_suggeree' => null,
            ]), 'mock', 0, 0),

            PromptType::EVALUATION => new RawAIResponse(json_encode([
                'score' => 4,
                'decision' => 'VALIDE',
                'feedback' => 'Bonne réponse.',
                'point_fort' => 'Compréhension correcte.',
                'point_amelioration' => 'Approfondis ce point.',
                'encouragement' => 'Continue ainsi.',
            ]), 'mock', 0, 0),

            PromptType::GUIDAGE => new RawAIResponse(
                'As-tu déjà rencontré un concept similaire dans tes ressources précédentes ?',
                'mock', 0, 0
            ),

            default => new RawAIResponse('{}', 'mock', 0, 0),
        };
    }
}
