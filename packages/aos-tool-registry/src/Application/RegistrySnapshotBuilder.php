<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\CapabilityRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\IntentRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ProviderRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\RegistrySnapshotBuilderInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Snapshot\RegistrySnapshot;
use DateTimeImmutable;

final class RegistrySnapshotBuilder implements RegistrySnapshotBuilderInterface
{
    public function __construct(
        private readonly ToolRegistryInterface $tools,
        private readonly CapabilityRegistryInterface $capabilities,
        private readonly IntentRegistryInterface $intents,
        private readonly ProviderRegistryInterface $providers,
    ) {}

    public function build(): RegistrySnapshot
    {
        $toolRows = [];
        foreach ($this->tools->all() as $tool) {
            $toolRows[] = $tool->metadata()->toArray() + [
                'providerId' => $tool->providerId(),
                'permission' => $tool->permission(),
            ];
        }

        $capabilityRows = [];
        foreach ($this->capabilities->all() as $capability) {
            $capabilityRows[] = [
                'name' => $capability->name(),
                'ownerDomain' => $capability->ownerDomain(),
                'description' => $capability->description(),
                'write' => $capability->write(),
            ];
        }

        $intentRows = [];
        foreach ($this->intents->all() as $intent) {
            $intentRows[] = [
                'intent' => $intent->intent(),
                'toolPlan' => $intent->toolPlan(),
                'requiredCapabilities' => $intent->requiredCapabilities(),
                'policy' => $intent->policy(),
                'approval' => $intent->approval(),
                'ownerDomain' => $intent->ownerDomain(),
            ];
        }

        $providerRows = [];
        foreach ($this->providers->all() as $provider) {
            $providerRows[] = [
                'id' => $provider->id(),
                'title' => $provider->title(),
                'version' => $provider->version(),
                'domains' => $provider->domains(),
                'healthy' => $provider->healthy(),
            ];
        }

        return new RegistrySnapshot(
            (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            $toolRows,
            $capabilityRows,
            $intentRows,
            $providerRows,
            count($toolRows),
            count($capabilityRows),
            count($intentRows),
        );
    }
}
