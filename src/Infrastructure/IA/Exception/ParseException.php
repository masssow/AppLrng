<?php

declare(strict_types=1);

namespace App\Infrastructure\IA\Exception;

/** JSON invalide reçu de l'IA — erreur définitive, UnrecoverableMessageHandlingException */
class ParseException extends \RuntimeException {}
