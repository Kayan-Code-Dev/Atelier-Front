<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application\Platform;

use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;
use RuntimeException;

/**
 * Plan-level subscription gate (Basic/Professional/Enterprise heuristic).
 */
final class SubscriptionValidator
{
    /**
     * @var array<string, list<string>>
     */
    private const PLAN_TOOLS = [
        'basic' => [
            'GetCustomer', 'SearchCustomer', 'CreateCustomer',
            'CheckAvailability', 'CreateReservation', 'CancelReservation',
        ],
        'professional' => [
            'GetCustomer', 'SearchCustomer', 'CreateCustomer',
            'CheckAvailability', 'CreateReservation', 'CancelReservation',
            'GenerateReport', 'GetInventory', 'CustomerInsights',
        ],
        'enterprise' => ['*'],
    ];

    public function assert(PlatformPlanningContext $context, ToolSelection $selection): void
    {
        $plan = strtolower($context->subscriptionPlan());
        $allowed = self::PLAN_TOOLS[$plan] ?? self::PLAN_TOOLS['basic'];
        if (in_array('*', $allowed, true)) {
            return;
        }

        foreach ($selection->selectedTools() as $tool) {
            if (! in_array($tool, $allowed, true)) {
                throw new RuntimeException('Subscription denied for tool: '.$tool.' on plan '.$plan);
            }
        }
    }
}
