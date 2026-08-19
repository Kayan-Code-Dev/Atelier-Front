<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Contracts;

interface CustomerIntentMapperInterface
{
    public function map(string $intent): ?string;
}
