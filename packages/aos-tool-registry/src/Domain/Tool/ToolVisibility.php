<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Tool;

enum ToolVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Restricted = 'restricted';
}
