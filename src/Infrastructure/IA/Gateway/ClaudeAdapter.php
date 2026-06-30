<?php

declare(strict_types=1);

namespace App\Infrastructure\IA\Gateway;

use App\Application\IA\DTO\PromptDTO;
use App\Application\IA\DTO\RawAIResponse;
use App\Application\IA\Port\IAGatewayInterface;
use App\Infrastructure\IA\Exception\AIGenerationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClaudeAdapter implements IAGatewayInterface
{
    private const MODEL      = 'claude-sonnet-4-6';
    private const MAX_TOKENS = 2048;
    private const API_URL    = 'https://api.anthropic.com/v1/messages';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $anthropicApiKey,
    ) {}

    public function complete(PromptDTO $prompt): RawAIResponse
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'x-api-key'         => $this->anthropicApiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'json' => [
                    'model'      => self::MODEL,
                    'max_tokens' => self::MAX_TOKENS,
                    'system'     => $prompt->system,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt->user],
                    ],
                    'temperature' => $prompt->temperature,
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            return new RawAIResponse(
                content: $data['content'][0]['text'] ?? '',
                model: $data['model'] ?? self::MODEL,
                inputTokens: $data['usage']['input_tokens'] ?? 0,
                outputTokens: $data['usage']['output_tokens'] ?? 0,
            );
        } catch (\Throwable $e) {
            throw new AIGenerationException(
                'Échec appel Claude API : ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
