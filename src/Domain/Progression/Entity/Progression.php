<?php

declare(strict_types=1);

namespace App\Domain\Progression\Entity;

use App\Domain\Parcours\Entity\Parcours;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'progression')]
#[ORM\UniqueConstraint(columns: ['parcours_id'])]
class Progression
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: Parcours::class, inversedBy: 'progression')]
    #[ORM\JoinColumn(nullable: false)]
    private Parcours $parcours;

    #[ORM\Column(type: 'integer')]
    private int $scoreConsolidation;

    #[ORM\Column(type: 'integer')]
    private int $scoreProjet;

    #[ORM\Column(type: 'integer')]
    private int $ressourcesTotal;

    #[ORM\Column(type: 'integer')]
    private int $ressourcesConsolidees;

    #[ORM\Column(type: 'json')]
    private array $sujetsFragiles;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $derniereActivite;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(Parcours $parcours)
    {
        $this->id                   = Uuid::v4();
        $this->parcours             = $parcours;
        $this->scoreConsolidation   = 0;
        $this->scoreProjet          = 0;
        $this->ressourcesTotal      = 0;
        $this->ressourcesConsolidees = 0;
        $this->sujetsFragiles       = [];
        $this->derniereActivite     = new \DateTime();
        $this->updatedAt            = new \DateTime();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getParcours(): Parcours
    {
        return $this->parcours;
    }

    public function getScoreConsolidation(): int
    {
        return $this->scoreConsolidation;
    }

    public function setScoreConsolidation(int $scoreConsolidation): void
    {
        $this->scoreConsolidation = $scoreConsolidation;
    }

    public function getScoreProjet(): int
    {
        return $this->scoreProjet;
    }

    public function setScoreProjet(int $scoreProjet): void
    {
        $this->scoreProjet = $scoreProjet;
    }

    public function getRessourcesTotal(): int
    {
        return $this->ressourcesTotal;
    }

    public function setRessourcesTotal(int $ressourcesTotal): void
    {
        $this->ressourcesTotal = $ressourcesTotal;
    }

    public function getRessourcesConsolidees(): int
    {
        return $this->ressourcesConsolidees;
    }

    public function setRessourcesConsolidees(int $ressourcesConsolidees): void
    {
        $this->ressourcesConsolidees = $ressourcesConsolidees;
    }

    public function getSujetsFragiles(): array
    {
        return $this->sujetsFragiles;
    }

    public function setSujetsFragiles(array $sujetsFragiles): void
    {
        $this->sujetsFragiles = $sujetsFragiles;
    }

    public function getDerniereActivite(): \DateTime
    {
        return $this->derniereActivite;
    }

    public function setDerniereActivite(\DateTime $derniereActivite): void
    {
        $this->derniereActivite = $derniereActivite;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
