<?php

declare(strict_types=1);

namespace App\Domain\Revision\Entity;

use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'revision_espacee')]
#[ORM\Index(columns: ['user_id'])]
#[ORM\Index(columns: ['date_prevue'])]
#[ORM\Index(columns: ['completee_at'])]
class RevisionSpacee
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

    #[ORM\Column(type: 'integer')]
    private int $iteration;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $datePrevue;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $completeeAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $score;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $reporteAt;

    public function __construct(
        Ressource $ressource,
        User $user,
        int $iteration,
        \DateTimeImmutable $datePrevue,
    ) {
        $this->id          = Uuid::v4();
        $this->ressource   = $ressource;
        $this->user        = $user;
        $this->iteration   = $iteration;
        $this->datePrevue  = $datePrevue;
        $this->completeeAt = null;
        $this->score       = null;
        $this->reporteAt   = null;
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

    public function getIteration(): int
    {
        return $this->iteration;
    }

    public function getDatePrevue(): \DateTimeImmutable
    {
        return $this->datePrevue;
    }

    public function setDatePrevue(\DateTimeImmutable $datePrevue): void
    {
        $this->datePrevue = $datePrevue;
    }

    public function getCompleteeAt(): ?\DateTime
    {
        return $this->completeeAt;
    }

    public function setCompleteeAt(?\DateTime $completeeAt): void
    {
        $this->completeeAt = $completeeAt;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): void
    {
        $this->score = $score;
    }

    public function getReporteAt(): ?\DateTime
    {
        return $this->reporteAt;
    }

    public function setReporteAt(?\DateTime $reporteAt): void
    {
        $this->reporteAt = $reporteAt;
    }
}
