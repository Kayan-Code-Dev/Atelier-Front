<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Contracts;

/**
 * Lightweight performance sample collector.
 */
interface PerformanceCollectorInterface
{
    public function start(string $operation): string;

    public function stop(string $token): void;

    /**
     * @return array<string, float> operation => last duration ms
     */
    public function samples(): array;
}
