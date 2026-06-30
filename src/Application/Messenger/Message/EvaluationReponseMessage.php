<?php

declare(strict_types=1);

namespace App\Application\Messenger\Message;

readonly class EvaluationReponseMessage
{
    public const TYPE_QUESTION = 'QUESTION';
    public const TYPE_EXERCICE = 'EXERCICE';

    public function __construct(
        public string $targetType,
        public string $targetId,
    ) {}
}
