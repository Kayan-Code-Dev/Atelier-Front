<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Tool;

enum ToolAvailability: string
{
    case Available = 'available';
    case Degraded = 'degraded';
    case Unavailable = 'unavailable';
}
