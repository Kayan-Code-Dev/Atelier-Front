<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Version\Contracts;

/**
 * Provides AOS platform and module version metadata.
 */
interface VersionProviderInterface
{
    public function platformVersion(): string;

    /**
     * @return array<string, string> map of module name => version
     */
    public function moduleVersions(): array;
}
