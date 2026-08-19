<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Mode;

/**
 * Built-in operating modes. Custom modes use OperatingModeCode::custom().
 */
enum OperatingMode: string
{
    case Assistant = 'assistant';
    case Hybrid = 'hybrid';
    case FullAuto = 'full_auto';
    case ReadOnly = 'read_only';
    case HumanOnly = 'human_only';
    case Maintenance = 'maintenance';
}
