<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Intent;

/**
 * Classification of resolved intent set shape.
 */
enum IntentKind: string
{
    case Single = 'single';
    case Multi = 'multi';
    case Ambiguous = 'ambiguous';
    case Conflicting = 'conflicting';
    case Unknown = 'unknown';
}
