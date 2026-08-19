<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Collection;

enum CollectionScope: string
{
    case Global = 'global';
    case Tenant = 'tenant';
    case Department = 'department';
    case Private = 'private';
    case Shared = 'shared';
}
