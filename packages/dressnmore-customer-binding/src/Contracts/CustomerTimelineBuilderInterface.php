<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;
use DressnMore\CustomerBinding\Domain\Timeline\CustomerTimeline;

interface CustomerTimelineBuilderInterface
{
    public function build(CustomerReadModel $customer): CustomerTimeline;
}
