<?php

declare(strict_types=1);

namespace App\Domain\Parcours\Entity;

use App\Domain\Parcours\Enum\StatutRessource;
use App\Domain\Parcours\Enum\TypeRessource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'ressource')]
#[ORM\Index(columns: ['parcours_id'])]
#[ORM\Index(columns: ['statut'])]
#[ORM\Index(columns: ['ordre'])]
class Ressource
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Parcours::class, inversedBy: 'ressources')]
    #[ORM\JoinColumn(nullable: false)]
    private Parcours $parcours;

    #[ORM\Column(type: 'string', length: 255)]
    private string $titre;

    #[ORM\Column(type: 'string', enumType: TypeRessource::class)]
    private TypeRessource $type;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $url;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $source;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'integer')]
    private int $ordre;

    #[ORM\Column(type: 'string', enumType: StatutRessource::class)]
    private StatutRessource $statut;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $dureeEstimeeMinutes;

    #[ORM\Column(name: 'pomodoros_suggeres', type: 'integer', nullable: true)]
    private ?int $pomodorosSuggeres;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $viewedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $consolidatedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        Parcours $parcours,
        string $titre,
        TypeRessource $type,
        int $ordre,
        ?string $url = null,
        ?string $source = null,
        ?string $description = null,
        ?int $dureeEstimeeMinutes = null,
    ) {
        $this->id                  = Uuid::v4();
        $this->parcours            = $parcours;
        $this->titre               = $titre;
        $this->type                = $type;
        $this->ordre               = $ordre;
        $this->url                 = $url;
        $this->source              = $source;
        $this->description         = $description;
        $this->dureeEstimeeMinutes = $dureeEstimeeMinutes;
        $this->pomodorosSuggeres   = null;
        $this->statut              = StatutRessource::A_FAIRE;
        $this->viewedAt            = null;
        $this->consolidatedAt      = null;
        $this->createdAt           = new \DateTimeImmutable();
        $this->updatedAt           = new \DateTime();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getParcours(): Parcours
    {
        return $this->parcours;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function getType(): TypeRessource
    {
        return $this->type;
    }

    public function setType(TypeRessource $type): void
    {
        $this->type = $type;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): void
    {
        $this->ordre = $ordre;
    }

    public function getStatut(): StatutRessource
    {
        return $this->statut;
    }

    public function setStatut(StatutRessource $statut): void
    {
        $this->statut = $statut;
    }

    public function getDureeEstimeeMinutes(): ?int
    {
        return $this->dureeEstimeeMinutes;
    }

    public function setDureeEstimeeMinutes(?int $dureeEstimeeMinutes): void
    {
        $this->dureeEstimeeMinutes = $dureeEstimeeMinutes;
    }

    public function getPomodorosSuggeres(): ?int
    {
        return $this->pomodorosSuggeres;
    }

    public function setPomodorosSuggeres(?int $pomodorosSuggeres): void
    {
        $this->pomodorosSuggeres = $pomodorosSuggeres;
    }

    public function getViewedAt(): ?\DateTime
    {
        return $this->viewedAt;
    }

    public function setViewedAt(?\DateTime $viewedAt): void
    {
        $this->viewedAt = $viewedAt;
    }

    public function getConsolidatedAt(): ?\DateTime
    {
        return $this->consolidatedAt;
    }

    public function setConsolidatedAt(?\DateTime $consolidatedAt): void
    {
        $this->consolidatedAt = $consolidatedAt;
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
}
