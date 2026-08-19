<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Strategy;

use DressnMore\Aos\Planner\Domain\Intent\IntentKind;

/**
 * Selects a planning strategy label for observability / future variants.
 */
final class PlanningStrategy
{
    public function forIntentKind(IntentKind $kind): string
    {
        return match ($kind) {
            IntentKind::Single => 'direct_single_goal',
            IntentKind::Multi => 'sequential_multi_goal',
            IntentKind::Ambiguous => 'clarify_first',
            IntentKind::Conflicting => 'clarify_conflict',
            IntentKind::Unknown => 'clarify_unknown',
        };
    }
}
