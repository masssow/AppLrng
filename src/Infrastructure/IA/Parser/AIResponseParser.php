<?php

declare(strict_types=1);

namespace App\Infrastructure\IA\Parser;

use App\Application\IA\DTO\RawAIResponse;
use App\Infrastructure\IA\Exception\ParseException;

class AIResponseParser
{
    public function parseJSON(RawAIResponse $raw): array
    {
        $content = trim($raw->content);

        // Supprimer les backticks ```json ... ``` ou ``` ... ```
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content);

        // Extraire le premier objet/tableau JSON si du texte précède
        if (!str_starts_with($content, '{') && !str_starts_with($content, '[')) {
            if (preg_match('/(\{[\s\S]*\}|\[[\s\S]*\])/u', $content, $matches)) {
                $content = $matches[1];
            }
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParseException(
                sprintf('JSON invalide reçu de l\'IA : %s', json_last_error_msg())
            );
        }

        return $data;
    }

    public function parseText(RawAIResponse $raw): string
    {
        return trim($raw->content);
    }
}
