<?php

declare(strict_types=1);

namespace App\Application\IA\Validator;

use App\Application\IA\DTO\ConsolidationDTO;
use App\Application\IA\DTO\EvaluationDTO;
use App\Application\IA\DTO\ExerciceDTO;
use App\Application\IA\DTO\OutilDTO;
use App\Application\IA\DTO\ParcoursStructureDTO;
use App\Application\IA\DTO\QuestionDTO;
use App\Infrastructure\IA\Exception\InvalidDTOException;

class AIResponseDTOValidator
{
    public function validate(array $data, string $dtoClass): object
    {
        return match ($dtoClass) {
            ConsolidationDTO::class   => $this->validateConsolidation($data),
            EvaluationDTO::class      => $this->validateEvaluation($data),
            ParcoursStructureDTO::class => $this->validateParcours($data),
            default                   => throw new InvalidDTOException("DTO inconnu : $dtoClass"),
        };
    }

    private function validateConsolidation(array $data): ConsolidationDTO
    {
        $this->requireFields($data, ['questions', 'exercice'], ConsolidationDTO::class);

        $questions = [];
        foreach ($data['questions'] as $q) {
            $questions[] = new QuestionDTO($q['texte'] ?? '', $q['type'] ?? 'comprehension');
        }

        $ex = $data['exercice'];
        $outil = null;
        if (!empty($ex['outil_suggere']) && is_array($ex['outil_suggere'])) {
            $outil = new OutilDTO(
                $ex['outil_suggere']['nom'] ?? '',
                $ex['outil_suggere']['url'] ?? '',
                $ex['outil_suggere']['instructions'] ?? '',
            );
        }
        $exercice = new ExerciceDTO(
            $ex['enonce'] ?? '',
            $outil,
            $ex['criteres_reussite'] ?? [],
        );

        return new ConsolidationDTO(
            $questions,
            $exercice,
            $data['niveau_difficulte'] ?? 'moyen',
            $data['concepts_cibles'] ?? [],
            $data['ressource_suivante_suggeree'] ?? null,
        );
    }

    private function validateEvaluation(array $data): EvaluationDTO
    {
        $this->requireFields($data, ['score', 'decision', 'feedback'], EvaluationDTO::class);

        $score = max(1, min(5, (int) ($data['score'] ?? 3)));

        return new EvaluationDTO(
            $score,
            $data['decision'] ?? 'PARTIEL',
            $data['feedback'] ?? '',
            $data['point_fort'] ?? '',
            $data['point_amelioration'] ?? '',
            $data['encouragement'] ?? '',
        );
    }

    private function validateParcours(array $data): ParcoursStructureDTO
    {
        $this->requireFields($data, ['themes', 'ordre_suggere', 'projet_fil_rouge'], ParcoursStructureDTO::class);

        return new ParcoursStructureDTO(
            $data['themes'] ?? [],
            $data['ordre_suggere'] ?? [],
            $data['prerequis_detectes'] ?? [],
            $data['risques_apprentissage'] ?? [],
            $data['projet_fil_rouge'] ?? [],
            $data['suggestion_ressource_manquante'] ?? null,
        );
    }

    private function requireFields(array $data, array $fields, string $dtoClass): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new InvalidDTOException(
                    sprintf('Champ requis manquant "%s" pour %s', $field, $dtoClass)
                );
            }
        }
    }
}
