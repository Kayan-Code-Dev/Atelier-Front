<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

interface RegistryExporterInterface
{
    /**
     * Conceptual export of the registered tool platform.
     *
     * @return array<string, mixed>
     */
    public function export(): array;
}
