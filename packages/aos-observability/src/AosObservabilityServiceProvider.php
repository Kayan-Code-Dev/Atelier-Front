<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Observability\Audit\LoggingAuditRecorder;
use DressnMore\Aos\Observability\Contracts\AuditRecorderInterface;
use DressnMore\Aos\Observability\Contracts\HealthReporterInterface;
use DressnMore\Aos\Observability\Contracts\LoggerInterface;
use DressnMore\Aos\Observability\Contracts\MetricsInterface;
use DressnMore\Aos\Observability\Contracts\PerformanceCollectorInterface;
use DressnMore\Aos\Observability\Contracts\TracerInterface;
use DressnMore\Aos\Observability\Health\HealthReporter;
use DressnMore\Aos\Observability\Logging\PsrLoggerAdapter;
use DressnMore\Aos\Observability\Metrics\NullMetrics;
use DressnMore\Aos\Observability\Module\ObservabilityModule;
use DressnMore\Aos\Observability\Performance\InMemoryPerformanceCollector;
use DressnMore\Aos\Observability\Tracing\NullTracer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * Registers AOS observability contracts and foundation adapters.
 */
final class AosObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoggerInterface::class, function ($app): LoggerInterface {
            $channel = (string) config('aos.logging.channel', config('logging.default', 'stack'));

            return new PsrLoggerAdapter(Log::channel($channel));
        });

        $this->app->singleton(AuditRecorderInterface::class, LoggingAuditRecorder::class);
        $this->app->singleton(MetricsInterface::class, NullMetrics::class);
        $this->app->singleton(TracerInterface::class, NullTracer::class);
        $this->app->singleton(HealthReporterInterface::class, HealthReporter::class);
        $this->app->singleton(PerformanceCollectorInterface::class, InMemoryPerformanceCollector::class);
        $this->app->singleton(ObservabilityModule::class);
    }

    public function boot(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('aos.observability')) {
            $registry->add($this->app->make(ObservabilityModule::class));
        }
    }
}
