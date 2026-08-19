<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Module;

use DressnMore\Aos\Communication\Application\CommunicationHub;
use DressnMore\Aos\Core\Module\AbstractModule;

final class CommunicationModule extends AbstractModule
{
    public function __construct(private readonly CommunicationHub $hub) {}

    public function name(): string
    {
        return $this->assertName('aos.communication');
    }

    public function title(): string
    {
        return 'AOS Omni-Channel Communication Hub';
    }

    public function version(): string
    {
        return '0.11.0';
    }

    public function isHealthy(): bool
    {
        return $this->hub instanceof CommunicationHub;
    }
}
