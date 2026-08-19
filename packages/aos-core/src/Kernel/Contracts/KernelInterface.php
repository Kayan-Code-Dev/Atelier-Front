<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Kernel\Contracts;

/**
 * Application kernel for the Agent Operating System foundation.
 *
 * Responsible for boot lifecycle only — no business, AI, or channel logic.
 */
interface KernelInterface
{
    /**
     * Run the foundation boot lifecycle and mark the platform ready.
     */
    public function boot(): void;

    /**
     * Whether the kernel has completed a successful boot.
     */
    public function isReady(): bool;

    /**
     * Current boot state label.
     */
    public function state(): string;

    /**
     * Platform semantic version from configuration.
     */
    public function version(): string;
}
