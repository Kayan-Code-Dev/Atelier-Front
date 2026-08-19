<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Providers;

use DressnMore\Aos\Context\Domain\Pipeline\ResolutionBag;

/**
 * Independent context provider — must not call other providers.
 */
interface ContextProviderInterface
{
    public function name(): string;

    public function contribute(ResolutionBag $bag): void;
}
