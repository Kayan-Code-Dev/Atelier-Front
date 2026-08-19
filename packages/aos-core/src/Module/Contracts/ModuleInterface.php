<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Module\Contracts;

/**
 * A discoverable AOS foundation module.
 */
interface ModuleInterface
{
    /**
     * Stable module key (e.g. aos.events).
     */
    public function name(): string;

    /**
     * Human-readable module title.
     */
    public function title(): string;

    /**
     * Semantic version of this module package.
     */
    public function version(): string;

    /**
     * Register module bindings into the application container.
     */
    public function register(): void;

    /**
     * Boot module after all modules are registered.
     */
    public function boot(): void;

    /**
     * Whether this module considers itself healthy.
     */
    public function isHealthy(): bool;
}
