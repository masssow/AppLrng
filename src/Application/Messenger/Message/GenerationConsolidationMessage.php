<?php

declare(strict_types=1);

namespace App\Application\Messenger\Message;

readonly class GenerationConsolidationMessage
{
    public function __construct(
        public string $sessionConsolidationId,
        public string $traceApprentissageId,
    ) {}
}
