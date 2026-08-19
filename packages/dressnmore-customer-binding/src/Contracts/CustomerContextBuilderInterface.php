<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

use DressnMore\CustomerBinding\Domain\Context\CustomerContext;
use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;

interface CustomerContextBuilderInterface
{
    public function build(CustomerReadModel $customer): CustomerContext;
}
