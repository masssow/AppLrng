<?php

declare(strict_types=1);

namespace App\Application\IA;

use App\Application\IA\DTO\ConsolidationDTO;
use App\Application\IA\DTO\EvaluationDTO;
use App\Application\IA\DTO\ParcoursStructureDTO;
use App\Application\IA\Port\IAGatewayInterface;
use App\Application\IA\Validator\AIResponseDTOValidator;
use App\Domain\Consolidation\Entity\TraceApprentissage;
use App\Domain\Projet\Entity\MicroEtape;
use App\Domain\Shared\Enum\NiveauMaitrise;
use App\Infrastructure\IA\Parser\AIResponseParser;

class AIOrchestrator
{
    public function __construct(
        private readonly IAGatewayInterface    $gateway,
        private readonly PromptBuilder         $promptBuilder,
        private readonly AIResponseParser      $parser,
        private readonly AIResponseDTOValidator $validator,
    ) {}

    public function genererConsolidation(
        TraceApprentissage $trace,
        string $domaine,
        NiveauMaitrise $niveau,
    ): ConsolidationDTO {
        $prompt   = $this->promptBuilder->buildConsolidationPrompt($trace, $domaine, $niveau);
        $raw      = $this->gateway->complete($prompt);
        $data     = $this->parser->parseJSON($raw);

        return $this->validator->validate($data, ConsolidationDTO::class);
    }

    public function evaluerReponse(
        string $questionOuEnonce,
        string $reponseUtilisateur,
        string $domaine,
        NiveauMaitrise $niveau,
    ): EvaluationDTO {
        $prompt = $this->promptBuilder->buildEvaluationPrompt($questionOuEnonce, $reponseUtilisateur, $domaine, $niveau);
        $raw    = $this->gateway->complete($prompt);
        $data   = $this->parser->parseJSON($raw);

        return $this->validator->validate($data, EvaluationDTO::class);
    }

    public function guiderMicroEtape(
        MicroEtape $etape,
        string $blocageDecrit,
        string $contexteProjet,
        array $historiqueConsolide,
        string $modeAccompagnement,
    ): string {
        $prompt = $this->promptBuilder->buildGuidagePrompt(
            $etape, $blocageDecrit, $contexteProjet, $historiqueConsolide, $modeAccompagnement
        );
        $raw = $this->gateway->complete($prompt);

        return $this->parser->parseText($raw);
    }

    public function structurerParcours(
        string $objectif,
        NiveauMaitrise $niveau,
        string $domaine,
        array $titresRessources,
    ): ParcoursStructureDTO {
        $prompt = $this->promptBuilder->buildParcoursPrompt($objectif, $niveau, $domaine, $titresRessources);
        $raw    = $this->gateway->complete($prompt);
        $data   = $this->parser->parseJSON($raw);

        return $this->validator->validate($data, ParcoursStructureDTO::class);
    }
}
