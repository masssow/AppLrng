<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Entity;

use App\Domain\Consolidation\Enum\StatutSession;
use App\Domain\Consolidation\Enum\TypeSession;
use App\Domain\Parcours\Entity\Ressource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'session_consolidation')]
#[ORM\Index(columns: ['ressource_id'])]
#[ORM\Index(columns: ['statut'])]
#[ORM\Index(columns: ['type'])]
class SessionConsolidation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Ressource::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Ressource $ressource;

    #[ORM\ManyToOne(targetEntity: TraceApprentissage::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?TraceApprentissage $traceApprentissage;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SessionConsolidation $parentSession;

    #[ORM\Column(type: 'string', enumType: TypeSession::class)]
    private TypeSession $type;

    #[ORM\Column(type: 'string', enumType: StatutSession::class)]
    private StatutSession $statut;

    #[ORM\Column(type: 'string')]
    private string $promptVersion;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $modeleIa;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $reponseIaBrute;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $generationError;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $regenerationReason;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $generatedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $completedAt;

    /** @var Collection<int, Question> */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'session')]
    private Collection $questions;

    #[ORM\OneToOne(targetEntity: Exercice::class, mappedBy: 'session')]
    private ?Exercice $exercice;

    public function __construct(
        Ressource $ressource,
        TypeSession $type,
        string $promptVersion,
        ?TraceApprentissage $traceApprentissage = null,
        ?SessionConsolidation $parentSession = null,
    ) {
        $this->id                   = Uuid::v4();
        $this->ressource            = $ressource;
        $this->type                 = $type;
        $this->promptVersion        = $promptVersion;
        $this->traceApprentissage   = $traceApprentissage;
        $this->parentSession        = $parentSession;
        $this->statut               = StatutSession::EN_ATTENTE;
        $this->modeleIa             = null;
        $this->reponseIaBrute       = null;
        $this->generationError      = null;
        $this->regenerationReason   = null;
        $this->generatedAt          = null;
        $this->completedAt          = null;
        $this->createdAt            = new \DateTimeImmutable();
        $this->questions            = new ArrayCollection();
        $this->exercice             = null;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRessource(): Ressource
    {
        return $this->ressource;
    }

    public function getTraceApprentissage(): ?TraceApprentissage
    {
        return $this->traceApprentissage;
    }

    public function setTraceApprentissage(?TraceApprentissage $traceApprentissage): void
    {
        $this->traceApprentissage = $traceApprentissage;
    }

    public function getParentSession(): ?SessionConsolidation
    {
        return $this->parentSession;
    }

    public function getType(): TypeSession
    {
        return $this->type;
    }

    public function getStatut(): StatutSession
    {
        return $this->statut;
    }

    public function setStatut(StatutSession $statut): void
    {
        $this->statut = $statut;
    }

    public function getPromptVersion(): string
    {
        return $this->promptVersion;
    }

    public function getModeleIa(): ?string
    {
        return $this->modeleIa;
    }

    public function setModeleIa(?string $modeleIa): void
    {
        $this->modeleIa = $modeleIa;
    }

    public function getReponseIaBrute(): ?array
    {
        return $this->reponseIaBrute;
    }

    public function setReponseIaBrute(?array $reponseIaBrute): void
    {
        $this->reponseIaBrute = $reponseIaBrute;
    }

    public function getGenerationError(): ?string
    {
        return $this->generationError;
    }

    public function setGenerationError(?string $generationError): void
    {
        $this->generationError = $generationError;
    }

    public function getRegenerationReason(): ?string
    {
        return $this->regenerationReason;
    }

    public function setRegenerationReason(?string $regenerationReason): void
    {
        $this->regenerationReason = $regenerationReason;
    }

    public function getGeneratedAt(): ?\DateTime
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(?\DateTime $generatedAt): void
    {
        $this->generatedAt = $generatedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTime $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    /** @return Collection<int, Question> */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }
}
