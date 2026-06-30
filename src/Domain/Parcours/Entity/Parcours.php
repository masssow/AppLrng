<?php

declare(strict_types=1);

namespace App\Domain\Parcours\Entity;

use App\Domain\Parcours\Enum\StatutParcours;
use App\Domain\Progression\Entity\Progression;
use App\Domain\Projet\Entity\ProjetFilRouge;
use App\Domain\Shared\Entity\Domaine;
use App\Domain\Shared\Entity\User;
use App\Domain\Shared\Enum\NiveauMaitrise;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'parcours')]
#[ORM\Index(columns: ['user_id'])]
#[ORM\Index(columns: ['statut'])]
#[ORM\Index(columns: ['deleted_at'])]
class Parcours
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Domaine::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Domaine $domaine;

    #[ORM\Column(type: 'string', length: 255)]
    private string $titre;

    #[ORM\Column(type: 'text')]
    private string $objectif;

    #[ORM\Column(type: 'string', enumType: NiveauMaitrise::class)]
    private NiveauMaitrise $niveau;

    #[ORM\Column(type: 'integer')]
    private int $dureeCibleSemaines;

    #[ORM\Column(type: 'string', enumType: StatutParcours::class)]
    private StatutParcours $statut;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $derniereEvalSurpriseAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt;

    /** @var Collection<int, Ressource> */
    #[ORM\OneToMany(targetEntity: Ressource::class, mappedBy: 'parcours')]
    private Collection $ressources;

    #[ORM\OneToOne(targetEntity: ProjetFilRouge::class, mappedBy: 'parcours')]
    private ?ProjetFilRouge $projetFilRouge;

    #[ORM\OneToOne(targetEntity: Progression::class, mappedBy: 'parcours')]
    private ?Progression $progression;

    public function __construct(
        User $user,
        Domaine $domaine,
        string $titre,
        string $objectif,
        NiveauMaitrise $niveau,
        int $dureeCibleSemaines,
    ) {
        $this->id                   = Uuid::v4();
        $this->user                 = $user;
        $this->domaine              = $domaine;
        $this->titre                = $titre;
        $this->objectif             = $objectif;
        $this->niveau               = $niveau;
        $this->dureeCibleSemaines   = $dureeCibleSemaines;
        $this->statut               = StatutParcours::BROUILLON;
        $this->derniereEvalSurpriseAt = null;
        $this->deletedAt            = null;
        $this->createdAt            = new \DateTimeImmutable();
        $this->updatedAt            = new \DateTime();
        $this->ressources      = new ArrayCollection();
        $this->projetFilRouge  = null;
        $this->progression     = null;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDomaine(): Domaine
    {
        return $this->domaine;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function getObjectif(): string
    {
        return $this->objectif;
    }

    public function setObjectif(string $objectif): void
    {
        $this->objectif = $objectif;
    }

    public function getNiveau(): NiveauMaitrise
    {
        return $this->niveau;
    }

    public function setNiveau(NiveauMaitrise $niveau): void
    {
        $this->niveau = $niveau;
    }

    public function getDureeCibleSemaines(): int
    {
        return $this->dureeCibleSemaines;
    }

    public function setDureeCibleSemaines(int $dureeCibleSemaines): void
    {
        $this->dureeCibleSemaines = $dureeCibleSemaines;
    }

    public function getStatut(): StatutParcours
    {
        return $this->statut;
    }

    public function setStatut(StatutParcours $statut): void
    {
        $this->statut = $statut;
    }

    public function getDerniereEvalSurpriseAt(): ?\DateTime
    {
        return $this->derniereEvalSurpriseAt;
    }

    public function setDerniereEvalSurpriseAt(?\DateTime $derniereEvalSurpriseAt): void
    {
        $this->derniereEvalSurpriseAt = $derniereEvalSurpriseAt;
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

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /** @return Collection<int, Ressource> */
    public function getRessources(): Collection
    {
        return $this->ressources;
    }

    public function getProjetFilRouge(): ?ProjetFilRouge
    {
        return $this->projetFilRouge;
    }

    public function getProgression(): ?Progression
    {
        return $this->progression;
    }
}
