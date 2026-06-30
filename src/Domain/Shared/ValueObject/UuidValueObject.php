<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use Symfony\Component\Uid\Uuid;

abstract class UuidValueObject
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function generate(): static
    {
        return new static(Uuid::v4()->toRfc4122());
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
