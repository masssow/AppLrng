<?php

declare(strict_types=1);

namespace App\Application\Messenger\Message;

readonly class GenerationParcoursMessage
{
    public function __construct(
        public string $parcoursId,
    ) {}
}
