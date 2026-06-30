<?php

declare(strict_types=1);

namespace App\Domain\Projet\Entity;

use App\Domain\Projet\Enum\StatutEtape;
use App\Domain\Projet\Enum\TypeMicroEtape;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'micro_etape')]
#[ORM\Index(columns: ['projet_id'])]
#[ORM\Index(columns: ['statut'])]
#[ORM\Index(columns: ['ordre'])]
class MicroEtape
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ProjetFilRouge::class, inversedBy: 'microEtapes')]
    #[ORM\JoinColumn(nullable: false)]
    private ProjetFilRouge $projet;

    #[ORM\Column(type: 'string', length: 255)]
    private string $titre;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', enumType: TypeMicroEtape::class)]
    private TypeMicroEtape $type;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $outilExterne;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $renduUtilisateur;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedbackIa;

    #[ORM\Column(type: 'string', enumType: StatutEtape::class)]
    private StatutEtape $statut;

    #[ORM\Column(type: 'integer')]
    private int $ordre;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $debloqueeAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $completedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $dernierePisteIa;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $dernierePisteAt;

    public function __construct(
        ProjetFilRouge $projet,
        string $titre,
        string $description,
        TypeMicroEtape $type,
        int $ordre,
    ) {
        $this->id              = Uuid::v4();
        $this->projet          = $projet;
        $this->titre           = $titre;
        $this->description     = $description;
        $this->type            = $type;
        $this->ordre           = $ordre;
        // Première étape disponible dès la création, les autres verrouillées
        $this->statut          = $ordre === 1 ? StatutEtape::DISPONIBLE : StatutEtape::VERROUILLEE;
        $this->outilExterne    = null;
        $this->renduUtilisateur = null;
        $this->feedbackIa      = null;
        $this->debloqueeAt     = $ordre === 1 ? new \DateTime() : null;
        $this->completedAt     = null;
        $this->dernierePisteIa = null;
        $this->dernierePisteAt = null;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProjet(): ProjetFilRouge
    {
        return $this->projet;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): TypeMicroEtape
    {
        return $this->type;
    }

    public function getOutilExterne(): ?array
    {
        return $this->outilExterne;
    }

    public function setOutilExterne(?array $outilExterne): void
    {
        $this->outilExterne = $outilExterne;
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

    public function getStatut(): StatutEtape
    {
        return $this->statut;
    }

    public function setStatut(StatutEtape $statut): void
    {
        $this->statut = $statut;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function getDebloqueeAt(): ?\DateTime
    {
        return $this->debloqueeAt;
    }

    public function setDebloqueeAt(?\DateTime $debloqueeAt): void
    {
        $this->debloqueeAt = $debloqueeAt;
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTime $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getDernierePisteIa(): ?string
    {
        return $this->dernierePisteIa;
    }

    public function setDernierePisteIa(?string $dernierePisteIa): void
    {
        $this->dernierePisteIa = $dernierePisteIa;
    }

    public function getDernierePisteAt(): ?\DateTime
    {
        return $this->dernierePisteAt;
    }

    public function setDernierePisteAt(?\DateTime $dernierePisteAt): void
    {
        $this->dernierePisteAt = $dernierePisteAt;
    }
}
