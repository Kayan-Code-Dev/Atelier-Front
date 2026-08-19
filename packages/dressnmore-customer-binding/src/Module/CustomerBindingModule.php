<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\CustomerBinding\Application\CustomerToolAdapter;

final class CustomerBindingModule extends AbstractModule
{
    public function __construct(private readonly CustomerToolAdapter $adapter) {}

    public function name(): string
    {
        return $this->assertName('dressnmore.customer.binding');
    }

    public function title(): string
    {
        return 'DressnMore Customer Domain Binding';
    }

    public function version(): string
    {
        return '0.14.0';
    }

    public function isHealthy(): bool
    {
        return $this->adapter->supports('GetCustomer');
    }
}
