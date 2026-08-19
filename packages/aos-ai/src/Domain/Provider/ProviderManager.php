<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Provider;

use DressnMore\Aos\Ai\Contracts\AiProviderInterface;
use DressnMore\Aos\Ai\Domain\Factory\ProviderFactory;
use DressnMore\Aos\Ai\Domain\Health\ProviderHealthMonitor;
use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Model\ModelRegistryInterface;

final class ProviderManager
{
    public function __construct(
        private readonly ProviderRegistryInterface $providers,
        private readonly ModelRegistryInterface $models,
        private readonly ProviderFactory $factory,
        private readonly ProviderHealthMonitor $health,
    ) {}

    public function register(ProviderDescriptor $descriptor, ?AiProviderInterface $plugin = null): void
    {
        $this->providers->register(
            $descriptor,
            $plugin ?? $this->factory->createStub($descriptor),
        );
        $this->health->probe($descriptor->id());
    }

    public function registerModel(ModelDescriptor $model): void
    {
        $this->models->register($model);
    }

    public function registry(): ProviderRegistryInterface
    {
        return $this->providers;
    }

    public function models(): ModelRegistryInterface
    {
        return $this->models;
    }

    public function health(): ProviderHealthMonitor
    {
        return $this->health;
    }
}
