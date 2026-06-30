<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Entity;

use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Shared\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'trace_apprentissage')]
#[ORM\Index(columns: ['ressource_id'])]
#[ORM\Index(columns: ['user_id'])]
class TraceApprentissage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Ressource::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Ressource $ressource;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comprisParUtilisateur;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $pointsFlous;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $applicationPossible;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $confianceUtilisateur;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $pomodorosEffectues;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    /** @var Collection<int, SessionConsolidation> */
    #[ORM\OneToMany(targetEntity: SessionConsolidation::class, mappedBy: 'traceApprentissage')]
    private Collection $sessions;

    public function __construct(
        Ressource $ressource,
        User $user,
    ) {
        $this->id                    = Uuid::v4();
        $this->ressource             = $ressource;
        $this->user                  = $user;
        $this->comprisParUtilisateur = null;
        $this->pointsFlous           = null;
        $this->applicationPossible   = null;
        $this->confianceUtilisateur  = null;
        $this->pomodorosEffectues    = null;
        $this->createdAt             = new \DateTimeImmutable();
        $this->updatedAt             = new \DateTime();
        $this->sessions              = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRessource(): Ressource
    {
        return $this->ressource;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getComprisParUtilisateur(): ?string
    {
        return $this->comprisParUtilisateur;
    }

    public function setComprisParUtilisateur(?string $comprisParUtilisateur): void
    {
        $this->comprisParUtilisateur = $comprisParUtilisateur;
    }

    public function getPointsFlous(): ?string
    {
        return $this->pointsFlous;
    }

    public function setPointsFlous(?string $pointsFlous): void
    {
        $this->pointsFlous = $pointsFlous;
    }

    public function getApplicationPossible(): ?string
    {
        return $this->applicationPossible;
    }

    public function setApplicationPossible(?string $applicationPossible): void
    {
        $this->applicationPossible = $applicationPossible;
    }

    public function getConfianceUtilisateur(): ?int
    {
        return $this->confianceUtilisateur;
    }

    public function setConfianceUtilisateur(?int $confianceUtilisateur): void
    {
        $this->confianceUtilisateur = $confianceUtilisateur;
    }

    public function getPomodorosEffectues(): ?int
    {
        return $this->pomodorosEffectues;
    }

    public function setPomodorosEffectues(?int $pomodorosEffectues): void
    {
        $this->pomodorosEffectues = $pomodorosEffectues;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /** @return Collection<int, SessionConsolidation> */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }
}
