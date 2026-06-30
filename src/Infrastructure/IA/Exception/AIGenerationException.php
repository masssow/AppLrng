<?php

declare(strict_types=1);

namespace App\Infrastructure\IA\Exception;

/** Erreur temporaire : timeout, API down, quota — éligible au retry Messenger */
class AIGenerationException extends \RuntimeException {}
