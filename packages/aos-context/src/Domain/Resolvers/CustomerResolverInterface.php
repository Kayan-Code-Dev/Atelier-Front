<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Resolvers;

use DressnMore\Aos\Context\Domain\Pipeline\ResolutionBag;

interface CustomerResolverInterface
{
    public function resolve(ResolutionBag $bag): void;
}
