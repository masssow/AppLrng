<?php

declare(strict_types=1);

namespace App\Application\IA\Port;

use App\Application\IA\DTO\PromptDTO;
use App\Application\IA\DTO\RawAIResponse;

interface IAGatewayInterface
{
    public function complete(PromptDTO $prompt): RawAIResponse;
}
