<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Contracts;

/**
 * Append-only audit recording port (foundation contract only).
 */
interface AuditRecorderInterface
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(string $action, array $context = []): void;
}
