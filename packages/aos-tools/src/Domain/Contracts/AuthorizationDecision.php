<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Contracts;

enum AuthorizationDecision: string
{
    case Allow = 'allow';
    case Deny = 'deny';
    case RequireApproval = 'require_approval';
}
