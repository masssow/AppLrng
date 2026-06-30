<?php

declare(strict_types=1);

namespace App\Domain\Shared\Entity;

use App\Domain\Shared\Enum\ModeAccompagnement;
use App\Domain\Shared\Enum\NiveauMaitrise;
use App\Domain\Shared\Enum\PlanUtilisateur;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\Index(columns: ['email'])]
#[ORM\Index(columns: ['deleted_at'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 100)]
    private string $prenom;

    #[ORM\Column(type: 'string', length: 100)]
    private string $nom;

    #[ORM\Column(type: 'string', enumType: NiveauMaitrise::class)]
    private NiveauMaitrise $niveau;

    #[ORM\Column(type: 'string', enumType: PlanUtilisateur::class)]
    private PlanUtilisateur $plan;

    #[ORM\Column(type: 'string', enumType: ModeAccompagnement::class)]
    private ModeAccompagnement $modeAccompagnement;

    #[ORM\Column(type: 'string', length: 2)]
    private string $langue;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $avatarUrl;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt;

    public function __construct(
        string $email,
        string $prenom,
        string $nom,
        NiveauMaitrise $niveau,
        string $langue = 'fr',
    ) {
        $this->id                 = Uuid::v4();
        $this->email              = $email;
        $this->prenom             = $prenom;
        $this->nom                = $nom;
        $this->niveau             = $niveau;
        $this->plan               = PlanUtilisateur::GRATUIT;
        $this->modeAccompagnement = ModeAccompagnement::SOCRATIQUE;
        $this->langue             = $langue;
        $this->avatarUrl          = null;
        $this->deletedAt          = null;
        $this->createdAt          = new \DateTimeImmutable();
        $this->updatedAt          = new \DateTime();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function eraseCredentials(): void
    {
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): void
    {
        $this->prenom = $prenom;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getNiveau(): NiveauMaitrise
    {
        return $this->niveau;
    }

    public function setNiveau(NiveauMaitrise $niveau): void
    {
        $this->niveau = $niveau;
    }

    public function getPlan(): PlanUtilisateur
    {
        return $this->plan;
    }

    public function setPlan(PlanUtilisateur $plan): void
    {
        $this->plan = $plan;
    }

    public function getModeAccompagnement(): ModeAccompagnement
    {
        return $this->modeAccompagnement;
    }

    public function setModeAccompagnement(ModeAccompagnement $modeAccompagnement): void
    {
        $this->modeAccompagnement = $modeAccompagnement;
    }

    public function getLangue(): string
    {
        return $this->langue;
    }

    public function setLangue(string $langue): void
    {
        $this->langue = $langue;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
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
}
