<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Goal;

use DressnMore\Aos\Planner\Domain\Intent\IntentCatalog;
use DressnMore\Aos\Planner\Domain\Intent\IntentCode;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolution;
use DressnMore\Aos\Planner\Domain\Intent\ResolvedIntent;

final class GoalResolver
{
    public function __construct(
        private readonly IntentCatalog $catalog = new IntentCatalog(),
    ) {}

    /**
     * @return list<PlanningGoal>
     */
    public function resolve(IntentResolution $resolution): array
    {
        if (! $resolution->isActionable()) {
            return [];
        }

        $rulesByCode = [];
        foreach ($this->catalog->rules() as $rule) {
            $rulesByCode[$rule['code']] = $rule;
        }

        $goals = [];
        $order = 0;
        foreach ($resolution->intents() as $intent) {
            $rule = $rulesByCode[$intent->code()->toString()] ?? null;
            if ($rule === null) {
                continue;
            }

            $goals[] = new PlanningGoal(
                GoalCode::fromString((string) ($rule['goal'] ?? $intent->code()->toString())),
                'Goal for intent '.$intent->code()->toString(),
                [$intent->code()],
                $rule['tools'] ?? [],
                100 + $order,
                (bool) ($rule['write'] ?? false),
            );
            $order++;
        }

        return $goals;
    }
}
