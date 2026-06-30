<?php

declare(strict_types=1);

namespace App\Application\IA;

use App\Application\IA\DTO\PromptDTO;
use App\Application\IA\Enum\PromptType;
use App\Application\IA\Strategy\StrategyResolver;
use App\Domain\Consolidation\Entity\TraceApprentissage;
use App\Domain\Projet\Entity\MicroEtape;
use App\Domain\Shared\Enum\NiveauMaitrise;

class PromptBuilder
{
    public function __construct(private readonly StrategyResolver $strategyResolver) {}

    public function buildConsolidationPrompt(
        TraceApprentissage $trace,
        string $domaine,
        NiveauMaitrise $niveau,
        string $version = PromptVersions::CONSOLIDATION_V1,
    ): PromptDTO {
        $strategy = $this->strategyResolver->resolve($domaine);
        $ressource = $trace->getRessource();

        $system = sprintf(
            "Tu es un tuteur pédagogique expert en %s.\n%s\nTu génères des questions de compréhension active, pas de mémorisation.\nLes exercices doivent être applicables immédiatement dans un contexte réel.\nRéponds uniquement en JSON valide, sans texte autour.",
            $domaine,
            $strategy->getSystemContext()
        );

        $user = sprintf(
            "Ressource étudiée : %s\nCe que l'apprenant dit avoir compris : %s\nPoints flous : %s\nApplication envisagée : %s\nNiveau : %s\nConfiance déclarée : %d/5\n\n%s\n\nRetourne ce JSON exact :\n{\n  \"questions\": [\n    { \"texte\": \"\", \"type\": \"comprehension|application|analyse\" },\n    { \"texte\": \"\", \"type\": \"\" },\n    { \"texte\": \"\", \"type\": \"\" }\n  ],\n  \"exercice\": {\n    \"enonce\": \"\",\n    \"outil_suggere\": { \"nom\": \"\", \"url\": \"\", \"instructions\": \"\" } ou null,\n    \"criteres_reussite\": []\n  },\n  \"niveau_difficulte\": \"facile|moyen|difficile\",\n  \"concepts_cibles\": [],\n  \"ressource_suivante_suggeree\": \"\" ou null\n}",
            $ressource->getTitre(),
            $trace->getComprisParUtilisateur() ?? '',
            $trace->getPointsFlous() ?? '',
            $trace->getApplicationPossible() ?? '',
            $niveau->value,
            $trace->getConfianceUtilisateur() ?? 3,
            $strategy->getConsolidationGuidelines()
        );

        return new PromptDTO(PromptType::CONSOLIDATION, $version, $system, $user, 'json', 0.7);
    }

    public function buildEvaluationPrompt(
        string $questionOuEnonce,
        string $reponseUtilisateur,
        string $domaine,
        NiveauMaitrise $niveau,
    ): PromptDTO {
        $strategy = $this->strategyResolver->resolve($domaine);

        $system = sprintf(
            "Tu es un tuteur bienveillant mais honnête.\nTu évalues une réponse ou un rendu d'exercice.\nTu donnes un feedback actionnable, pas une note sèche.\n%s\nRéponds uniquement en JSON valide, sans texte autour.",
            $strategy->getEvaluationGuidelines()
        );

        $user = sprintf(
            "Question posée / Énoncé exercice : %s\nRéponse / Rendu de l'apprenant : %s\nDomaine : %s\nNiveau : %s\n\nRetourne ce JSON exact :\n{\n  \"score\": 1-5,\n  \"decision\": \"A_REVOIR|PARTIEL|VALIDE\",\n  \"feedback\": \"\",\n  \"point_fort\": \"\",\n  \"point_amelioration\": \"\",\n  \"encouragement\": \"\"\n}",
            $questionOuEnonce,
            $reponseUtilisateur,
            $domaine,
            $niveau->value
        );

        return new PromptDTO(PromptType::EVALUATION, PromptVersions::EVALUATION_V1, $system, $user, 'json', 0.5);
    }

    public function buildGuidagePrompt(
        MicroEtape $etape,
        string $blocageDecrit,
        string $contexteProjet,
        array $historiqueConsolide,
        string $modeAccompagnement,
    ): PromptDTO {
        $system = match (strtoupper($modeAccompagnement)) {
            'MIXTE'      => "Tu es un tuteur bienveillant.\nTu poses une question de guidage, puis donnes un indice progressif.\nTu t'appuies sur les concepts consolidés dans le parcours.\nMaximum 4 phrases. Réponds en texte simple, sans JSON.",
            'EXPLICATIF' => "Tu es un formateur clair et direct.\nTu expliques le concept bloqué de façon simple et concrète.\nTu relies l'explication aux acquis du parcours de l'apprenant.\nMaximum 5 phrases. Réponds en texte simple, sans JSON.",
            default      => "Tu es un mentor Socratique.\nTu utilises l'historique du parcours pour faire des ponts.\nTu poses UNE question qui amène l'apprenant à trouver lui-même.\nMaximum 3 phrases. Réponds en texte simple, sans JSON.",
        };

        $historique = implode("\n", array_map(
            fn($h) => sprintf('  - %s : %s (%d Pomodoros)', $h['titre'], $h['concepts'] ?? '', $h['pomodoros'] ?? 0),
            $historiqueConsolide
        ));

        $user = sprintf(
            "Contexte du projet : %s\nÉtape bloquante : %s — %s\nBlocage décrit par l'apprenant : %s\nMode d'accompagnement : %s\n\nHistorique consolidé du parcours :\n%s",
            $contexteProjet,
            $etape->getTitre(),
            $etape->getDescription(),
            $blocageDecrit,
            strtoupper($modeAccompagnement),
            $historique ?: '  (aucun)'
        );

        $version = match (strtoupper($modeAccompagnement)) {
            'MIXTE'      => PromptVersions::GUIDAGE_MIXTE_V1,
            'EXPLICATIF' => PromptVersions::GUIDAGE_EXPLICATIF_V1,
            default      => PromptVersions::GUIDAGE_SOCRATIQUE_V1,
        };

        return new PromptDTO(PromptType::GUIDAGE, $version, $system, $user, 'text', 0.8);
    }

    public function buildParcoursPrompt(
        string $objectif,
        NiveauMaitrise $niveau,
        string $domaine,
        array $ressources,
    ): PromptDTO {
        $system = sprintf(
            "Tu es un architecte pédagogique expert en %s.\nTon rôle est de structurer une liste de ressources brutes en parcours d'apprentissage logique.\nTu ne juges pas la qualité des ressources — tu les organises.\nRéponds uniquement en JSON valide, sans texte autour.",
            $domaine
        );

        $lignesRessources = implode("\n", array_map(
            fn($r) => sprintf('  %s: %s', $r['ref'], $r['titre']),
            $ressources
        ));

        $user = sprintf(
            "Objectif de l'apprenant : %s\nNiveau déclaré : %s\nDomaine : %s\nRessources :\n%s\n\nRetourne ce JSON exact :\n{\n  \"themes\": [{ \"titre\": \"\", \"ressources_refs\": [\"r1\", \"r2\"] }],\n  \"ordre_suggere\": [\"r1\", \"r3\", \"r2\"],\n  \"prerequis_detectes\": [],\n  \"risques_apprentissage\": [],\n  \"projet_fil_rouge\": {\n    \"titre\": \"\",\n    \"description\": \"\",\n    \"micro_etapes\": [{ \"titre\": \"\", \"type\": \"LECTURE|EXERCICE|OUTIL_EXTERNE|LIVRABLE\", \"description\": \"\" }]\n  },\n  \"suggestion_ressource_manquante\": \"\" ou null\n}",
            $objectif,
            $niveau->value,
            $domaine,
            $lignesRessources
        );

        return new PromptDTO(PromptType::PARCOURS, PromptVersions::PARCOURS_V1, $system, $user, 'json', 0.4);
    }
}
