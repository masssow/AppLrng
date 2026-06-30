<?php

declare(strict_types=1);

namespace App\Application\IA\Strategy;

class StrategyResolver
{
    /** @var DomainPromptStrategyInterface[] */
    private array $strategies;

    /**
     * @param iterable<DomainPromptStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies, private readonly DefaultStrategy $defaultStrategy)
    {
        $this->strategies = iterator_to_array($strategies);
    }

    public function resolve(string $strategieKey): DomainPromptStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if (!$strategy instanceof DefaultStrategy && $strategy->supportsDomaine($strategieKey)) {
                return $strategy;
            }
        }

        return $this->defaultStrategy;
    }
}
