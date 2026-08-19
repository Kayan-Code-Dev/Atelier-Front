<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Tool;

enum ToolRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
