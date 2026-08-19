<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Observability\Contracts\LoggerInterface;
use DressnMore\Aos\Observability\Contracts\MetricsInterface;
use DressnMore\Aos\Observability\Contracts\TracerInterface;

/**
 * Foundation observability module.
 */
final class ObservabilityModule extends AbstractModule
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MetricsInterface $metrics,
        private readonly TracerInterface $tracer,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.observability');
    }

    public function title(): string
    {
        return 'AOS Observability';
    }

    public function version(): string
    {
        return '0.1.0';
    }

    public function boot(): void
    {
        $this->logger->info('AOS observability module booted.', [
            'module' => $this->name(),
            'version' => $this->version(),
        ]);
        $this->metrics->increment('aos.observability.boot');
    }

    public function isHealthy(): bool
    {
        return true;
    }
}
