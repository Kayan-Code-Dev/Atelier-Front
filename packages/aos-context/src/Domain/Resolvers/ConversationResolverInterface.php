<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Resolvers;

use DressnMore\Aos\Context\Domain\Pipeline\ResolutionBag;

interface ConversationResolverInterface
{
    public function resolve(ResolutionBag $bag): void;
}
