<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Contracts;

use DressnMore\Aos\Planner\Domain\Platform\AnalyzedIntent;
use DressnMore\Aos\Planner\Domain\Platform\CapabilityMatch;

interface CapabilityMatcherInterface
{
    public function match(AnalyzedIntent $intent): CapabilityMatch;
}
