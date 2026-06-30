<?php

declare(strict_types=1);

namespace App\Domain\Shared\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'domaine')]
class Domaine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string')]
    private string $label;

    #[ORM\Column(type: 'string')]
    private string $icone;

    #[ORM\Column(type: 'string')]
    private string $strategieKey;

    public function __construct(
        string $slug,
        string $label,
        string $icone,
        string $strategieKey,
    ) {
        $this->slug         = $slug;
        $this->label        = $label;
        $this->icone        = $icone;
        $this->strategieKey = $strategieKey;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcone(): string
    {
        return $this->icone;
    }

    public function getStrategieKey(): string
    {
        return $this->strategieKey;
    }
}
