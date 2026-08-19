<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Tool;

enum ToolStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Deprecated = 'deprecated';
    case Disabled = 'disabled';
}
