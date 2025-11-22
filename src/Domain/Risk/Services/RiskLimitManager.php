<?php

namespace TradingPlatform\Domain\Risk\Services;

class RiskLimitManager
{
    private array $limits = [];

    public function setLimit(string $level, string $entityId, string $metric, float $value): void
    {
        // Level: 'PORTFOLIO', 'STRATEGY', 'INSTRUMENT'
        $this->limits[$level][$entityId][$metric] = $value;
    }

    public function checkLimits(string $level, string $entityId, array $metrics): array
    {
        $violations = [];
        $definedLimits = $this->limits[$level][$entityId] ?? [];

        foreach ($definedLimits as $metric => $limit) {
            if (isset($metrics[$metric]) && $metrics[$metric] > $limit) {
                $violations[] = [
                    'metric' => $metric,
                    'limit' => $limit,
                    'current' => $metrics[$metric]
                ];
            }
        }

        return [
            'approved' => empty($violations),
            'violations' => $violations
        ];
    }

    public function getHierarchicalLimits(string $strategyId): array
    {
        // Return merged limits for Strategy + Global Portfolio
        return [
            'strategy' => $this->limits['STRATEGY'][$strategyId] ?? [],
            'portfolio' => $this->limits['PORTFOLIO']['GLOBAL'] ?? []
        ];
    }
}
