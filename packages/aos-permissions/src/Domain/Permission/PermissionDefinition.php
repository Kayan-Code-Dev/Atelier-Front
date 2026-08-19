<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Permission;

final class PermissionDefinition
{
    public function __construct(
        private readonly PermissionCode $code,
        private readonly string $description,
    ) {}

    public function code(): PermissionCode
    {
        return $this->code;
    }

    public function description(): string
    {
        return $this->description;
    }
}
