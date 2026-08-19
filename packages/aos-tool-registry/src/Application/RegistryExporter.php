<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\RegistryExporterInterface;
use DressnMore\Aos\ToolRegistry\Contracts\RegistrySnapshotBuilderInterface;

final class RegistryExporter implements RegistryExporterInterface
{
    public function __construct(private readonly RegistrySnapshotBuilderInterface $snapshots) {}

    public function export(): array
    {
        $snapshot = $this->snapshots->build();

        return [
            'format' => 'aos-tool-registry.conceptual.v1',
            'platform' => 'AOS Tool Registry & Capability Platform',
            'version' => '0.16.0',
            'snapshot' => $snapshot->toArray(),
            'categories' => array_map(
                static fn ($c) => $c->value,
                \DressnMore\Aos\ToolRegistry\Domain\Tool\ToolCategory::cases(),
            ),
        ];
    }
}
