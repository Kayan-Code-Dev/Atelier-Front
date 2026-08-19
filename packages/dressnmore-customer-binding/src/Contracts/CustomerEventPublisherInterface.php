<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

interface CustomerEventPublisherInterface
{
    public function publish(object $event): void;
}
