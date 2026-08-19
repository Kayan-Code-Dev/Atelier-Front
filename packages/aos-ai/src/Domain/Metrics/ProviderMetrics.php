<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Metrics;

use DressnMore\Aos\Ai\Domain\Provider\ProviderId;

final class ProviderMetrics
{
    /** @var array<string, array{requests: int, failures: int, latency_sum: int, cost_sum: float}> */
    private array $stats = [];

    public function recordSuccess(ProviderId $id, int $latencyMs, float $costUsd): void
    {
        $key = $id->toString();
        $this->ensure($key);
        $this->stats[$key]['requests']++;
        $this->stats[$key]['latency_sum'] += $latencyMs;
        $this->stats[$key]['cost_sum'] += $costUsd;
    }

    public function recordFailure(ProviderId $id): void
    {
        $key = $id->toString();
        $this->ensure($key);
        $this->stats[$key]['requests']++;
        $this->stats[$key]['failures']++;
    }

    /** @return array<string, mixed> */
    public function snapshot(ProviderId $id): array
    {
        $key = $id->toString();
        $this->ensure($key);
        $s = $this->stats[$key];
        $ok = max(1, $s['requests'] - $s['failures']);

        return [
            'provider' => $key,
            'requests' => $s['requests'],
            'failures' => $s['failures'],
            'avg_latency_ms' => (int) round($s['latency_sum'] / $ok),
            'total_cost_usd' => round($s['cost_sum'], 6),
        ];
    }

    private function ensure(string $key): void
    {
        if (! isset($this->stats[$key])) {
            $this->stats[$key] = [
                'requests' => 0,
                'failures' => 0,
                'latency_sum' => 0,
                'cost_sum' => 0.0,
            ];
        }
    }
}
