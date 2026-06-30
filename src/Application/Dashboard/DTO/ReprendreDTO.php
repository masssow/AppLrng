<?php

declare(strict_types=1);

namespace App\Application\Dashboard\DTO;

final class ReprendreDTO
{
    public function __construct(
        /** 'consolidation'|'micro_etape'|'ressource' */
        public readonly string $type,
        public readonly string $titre,
        public readonly string $route,
        /** @var array<string, string> */
        public readonly array $routeParams,
        public readonly string $contexte,
    ) {}
}
