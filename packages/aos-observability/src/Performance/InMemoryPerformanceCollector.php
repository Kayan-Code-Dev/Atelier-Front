<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Performance;

use DressnMore\Aos\Observability\Contracts\PerformanceCollectorInterface;
use Illuminate\Support\Str;

/**
 * In-memory performance sample collector for foundation diagnostics.
 */
final class InMemoryPerformanceCollector implements PerformanceCollectorInterface
{
    /** @var array<string, float> */
    private array $open = [];

    /** @var array<string, float> */
    private array $samples = [];

    public function start(string $operation): string
    {
        $token = $operation.':'.Str::uuid()->toString();
        $this->open[$token] = microtime(true);

        return $token;
    }

    public function stop(string $token): void
    {
        if (! isset($this->open[$token])) {
            return;
        }

        $started = $this->open[$token];
        unset($this->open[$token]);

        $operation = explode(':', $token, 2)[0];
        $this->samples[$operation] = (microtime(true) - $started) * 1000;
    }

    public function samples(): array
    {
        return $this->samples;
    }
}
