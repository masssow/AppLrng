<?php

declare(strict_types=1);

namespace App\Domain\Projet\Entity;

use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Projet\Enum\StatutProjet;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'projet_fil_rouge')]
#[ORM\UniqueConstraint(columns: ['parcours_id'])]
class ProjetFilRouge
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: Parcours::class, inversedBy: 'projetFilRouge')]
    #[ORM\JoinColumn(nullable: false)]
    private Parcours $parcours;

    #[ORM\Column(type: 'string', length: 255)]
    private string $titre;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', enumType: StatutProjet::class)]
    private StatutProjet $statut;

    #[ORM\Column(type: 'string')]
    private string $promptVersion;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $reponseIaBrute;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, MicroEtape> */
    #[ORM\OneToMany(targetEntity: MicroEtape::class, mappedBy: 'projet')]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $microEtapes;

    public function __construct(
        Parcours $parcours,
        string $titre,
        string $description,
        string $promptVersion,
    ) {
        $this->id            = Uuid::v4();
        $this->parcours      = $parcours;
        $this->titre         = $titre;
        $this->description   = $description;
        $this->promptVersion = $promptVersion;
        $this->statut        = StatutProjet::NON_DEMARRE;
        $this->reponseIaBrute = null;
        $this->createdAt     = new \DateTimeImmutable();
        $this->microEtapes   = new ArrayCollection();
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getStatut(): StatutProjet
    {
        return $this->statut;
    }

    public function setStatut(StatutProjet $statut): void
    {
        $this->statut = $statut;
    }

    public function getPromptVersion(): string
    {
        return $this->promptVersion;
    }

    public function getReponseIaBrute(): ?array
    {
        return $this->reponseIaBrute;
    }

    public function setReponseIaBrute(?array $reponseIaBrute): void
    {
        $this->reponseIaBrute = $reponseIaBrute;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, MicroEtape> */
    public function getMicroEtapes(): Collection
    {
        return $this->microEtapes;
    }
}
