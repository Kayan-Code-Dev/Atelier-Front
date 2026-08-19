<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Confidence;

use DressnMore\Aos\Planner\Domain\Intent\IntentResolution;
use DressnMore\Aos\Planner\Domain\Task\PlannedTask;

final class ConfidenceEvaluator
{
    /**
     * @param  list<PlannedTask>  $tasks
     */
    public function evaluate(IntentResolution $resolution, array $tasks): float
    {
        $base = $resolution->overallConfidence();
        if ($tasks === []) {
            return round($base * 0.5, 4);
        }

        $penalty = 0.0;
        if (count($tasks) > 6) {
            $penalty += 0.1;
        }

        return max(0.0, min(1.0, round($base - $penalty, 4)));
    }
}
