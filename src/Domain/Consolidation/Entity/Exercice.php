<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Entity;

use App\Domain\Consolidation\Enum\StatutEvaluation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'exercice')]
class Exercice
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: SessionConsolidation::class, inversedBy: 'exercice')]
    #[ORM\JoinColumn(nullable: false)]
    private SessionConsolidation $session;

    #[ORM\Column(type: 'text')]
    private string $enonce;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $outilSuggere;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $renduUtilisateur;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedbackIa;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $feedbackScore;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $decision;

    #[ORM\Column(type: 'string', enumType: StatutEvaluation::class)]
    private StatutEvaluation $statutEvaluation;

    public function __construct(
        SessionConsolidation $session,
        string $enonce,
    ) {
        $this->id               = Uuid::v4();
        $this->session          = $session;
        $this->enonce           = $enonce;
        $this->outilSuggere     = null;
        $this->renduUtilisateur = null;
        $this->feedbackIa       = null;
        $this->feedbackScore    = null;
        $this->decision         = null;
        $this->statutEvaluation = StatutEvaluation::EN_ATTENTE;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSession(): SessionConsolidation
    {
        return $this->session;
    }

    public function getEnonce(): string
    {
        return $this->enonce;
    }

    public function getOutilSuggere(): ?array
    {
        return $this->outilSuggere;
    }

    public function setOutilSuggere(?array $outilSuggere): void
    {
        $this->outilSuggere = $outilSuggere;
    }

    public function getRenduUtilisateur(): ?string
    {
        return $this->renduUtilisateur;
    }

    public function setRenduUtilisateur(?string $renduUtilisateur): void
    {
        $this->renduUtilisateur = $renduUtilisateur;
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

    public function getStatutEvaluation(): StatutEvaluation
    {
        return $this->statutEvaluation;
    }

    public function setStatutEvaluation(StatutEvaluation $statutEvaluation): void
    {
        $this->statutEvaluation = $statutEvaluation;
    }
}
