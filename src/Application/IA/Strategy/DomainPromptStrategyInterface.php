<?php

declare(strict_types=1);

namespace App\Application\IA\Strategy;

interface DomainPromptStrategyInterface
{
    public function supportsDomaine(string $key): bool;
    public function getSystemContext(): string;
    public function getConsolidationGuidelines(): string;
    public function getEvaluationGuidelines(): string;
    public function getExerciseGuidelines(): string;
    public function getSuggestedTools(): array;
}
