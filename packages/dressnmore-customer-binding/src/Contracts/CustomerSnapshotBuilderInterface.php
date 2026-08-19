<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;
use DressnMore\CustomerBinding\Domain\Snapshot\CustomerSnapshot;

interface CustomerSnapshotBuilderInterface
{
    public function build(CustomerReadModel $customer): CustomerSnapshot;
}
