<?php

declare(strict_types=1);

namespace App\Infrastructure\IA\Gateway;

use App\Application\IA\DTO\PromptDTO;
use App\Application\IA\DTO\RawAIResponse;
use App\Application\IA\Port\IAGatewayInterface;
use App\Infrastructure\IA\Exception\AIGenerationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqAdapter implements IAGatewayInterface
{
    private const MODEL      = 'llama-3.3-70b-versatile';
    private const MAX_TOKENS = 2048;
    private const API_URL    = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $groqApiKey,
    ) {}

    public function complete(PromptDTO $prompt): RawAIResponse
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => self::MODEL,
                    'max_tokens'  => self::MAX_TOKENS,
                    'temperature' => $prompt->temperature,
                    'messages'    => [
                        ['role' => 'system', 'content' => $prompt->system],
                        ['role' => 'user',   'content' => $prompt->user],
                    ],
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            return new RawAIResponse(
                content: $data['choices'][0]['message']['content'] ?? '',
                model: $data['model'] ?? self::MODEL,
                inputTokens: $data['usage']['prompt_tokens'] ?? 0,
                outputTokens: $data['usage']['completion_tokens'] ?? 0,
            );
        } catch (\Throwable $e) {
            throw new AIGenerationException(
                'Échec appel Groq API : ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
