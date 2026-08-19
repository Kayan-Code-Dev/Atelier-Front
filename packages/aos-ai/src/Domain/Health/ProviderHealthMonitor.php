<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Health;

use DressnMore\Aos\Ai\Domain\Provider\ProviderId;
use DressnMore\Aos\Ai\Domain\Provider\ProviderRegistryInterface;

final class ProviderHealthMonitor
{
    /** @var array<string, HealthStatus> */
    private array $statuses = [];

    public function __construct(
        private readonly ProviderRegistryInterface $registry,
    ) {}

    public function mark(ProviderId $id, HealthStatus $status): void
    {
        $this->statuses[$id->toString()] = $status;
        $descriptor = $this->registry->get($id);
        if ($descriptor !== null) {
            $this->registry->update($descriptor->withHealth($status));
        }
    }

    public function markHealthy(ProviderId $id): void
    {
        $this->mark($id, HealthStatus::Healthy);
    }

    public function markUnhealthy(ProviderId $id): void
    {
        $this->mark($id, HealthStatus::Unhealthy);
    }

    public function status(ProviderId $id): HealthStatus
    {
        return $this->statuses[$id->toString()]
            ?? $this->registry->get($id)?->health()
            ?? HealthStatus::Unknown;
    }

    public function probe(ProviderId $id): HealthStatus
    {
        $plugin = $this->registry->plugin($id);
        if ($plugin === null) {
            $this->mark($id, HealthStatus::Unknown);

            return HealthStatus::Unknown;
        }

        $status = $plugin->isAvailable() ? HealthStatus::Healthy : HealthStatus::Unhealthy;
        $this->mark($id, $status);

        return $status;
    }
}
