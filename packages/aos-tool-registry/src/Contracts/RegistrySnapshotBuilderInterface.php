<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Snapshot\RegistrySnapshot;

interface RegistrySnapshotBuilderInterface
{
    public function build(): RegistrySnapshot;
}
