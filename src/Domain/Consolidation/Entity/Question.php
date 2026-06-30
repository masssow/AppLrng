<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Entity;

use App\Domain\Consolidation\Enum\StatutEvaluation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'question')]
#[ORM\Index(columns: ['session_id'])]
#[ORM\Index(columns: ['ordre'])]
class Question
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: SessionConsolidation::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private SessionConsolidation $session;

    #[ORM\Column(type: 'text')]
    private string $texte;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reponseUtilisateur;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedbackIa;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $feedbackScore;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $decision;

    #[ORM\Column(type: 'boolean')]
    private bool $validee;

    #[ORM\Column(type: 'string', enumType: StatutEvaluation::class)]
    private StatutEvaluation $statutEvaluation;

    #[ORM\Column(type: 'integer')]
    private int $ordre;

    public function __construct(
        SessionConsolidation $session,
        string $texte,
        int $ordre,
    ) {
        $this->id                 = Uuid::v4();
        $this->session            = $session;
        $this->texte              = $texte;
        $this->ordre              = $ordre;
        $this->reponseUtilisateur = null;
        $this->feedbackIa         = null;
        $this->feedbackScore      = null;
        $this->decision           = null;
        $this->validee            = false;
        $this->statutEvaluation   = StatutEvaluation::EN_ATTENTE;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSession(): SessionConsolidation
    {
        return $this->session;
    }

    public function getTexte(): string
    {
        return $this->texte;
    }

    public function getReponseUtilisateur(): ?string
    {
        return $this->reponseUtilisateur;
    }

    public function setReponseUtilisateur(?string $reponseUtilisateur): void
    {
        $this->reponseUtilisateur = $reponseUtilisateur;
    }

    public function getFeedbackIa(): ?string
    {
        return $this->feedbackIa;
    }

    public function setFeedbackIa(?string $feedbackIa): void
    {
        $this->feedbackIa = $feedbackIa;
    }

    public function getFeedbackScore(): ?int
    {
        return $this->feedbackScore;
    }

    public function setFeedbackScore(?int $feedbackScore): void
    {
        $this->feedbackScore = $feedbackScore;
    }

    public function getDecision(): ?string
    {
        return $this->decision;
    }

    public function setDecision(?string $decision): void
    {
        $this->decision = $decision;
    }

    public function isValidee(): bool
    {
        return $this->validee;
    }

    public function setValidee(bool $validee): void
    {
        $this->validee = $validee;
    }

    public function getStatutEvaluation(): StatutEvaluation
    {
        return $this->statutEvaluation;
    }

    public function setStatutEvaluation(StatutEvaluation $statutEvaluation): void
    {
        $this->statutEvaluation = $statutEvaluation;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }
}
