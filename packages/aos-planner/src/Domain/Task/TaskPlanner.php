<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Task;

use DressnMore\Aos\Planner\Domain\Goal\PlanningGoal;
use DressnMore\Aos\Planner\Domain\Intent\IntentCatalog;

/**
 * Decomposes goals into ordered tasks with tool candidates (no execution).
 */
final class TaskPlanner
{
    public function __construct(
        private readonly IntentCatalog $catalog = new IntentCatalog(),
    ) {}

    /**
     * @param  list<PlanningGoal>  $goals
     * @return list<PlannedTask>
     */
    public function decompose(array $goals): array
    {
        $tasks = [];
        $identityTaskId = null;
        $order = 0;
        /** @var list<string> $allReadTaskIds */
        $allReadTaskIds = [];

        // Always prefer identity before money/status/writes when customer-facing tools appear.
        $needsIdentity = false;
        foreach ($goals as $goal) {
            foreach ($goal->toolCandidates() as $tool) {
                if (in_array($tool, ['GetOutstandingBalance', 'CreateReservation', 'GetOrderStatus', 'ListOpenOrdersForCustomer'], true)) {
                    $needsIdentity = true;
                }
            }
        }

        if ($needsIdentity) {
            $identityTaskId = TaskId::generate('identity');
            $tasks[] = new PlannedTask(
                $identityTaskId,
                $goals[0]->code(),
                'Identify customer before domain reads/writes',
                ['SearchCustomer', 'GetCustomerProfile'],
                [],
                false,
                false,
                $order++,
            );
            $allReadTaskIds[] = $identityTaskId->toString();
        }

        $approvalMap = $this->approvalByGoal();

        // Process read-only goals first, then write goals — keeps multi-intent plans valid.
        $sortedGoals = $goals;
        usort(
            $sortedGoals,
            static fn ($a, $b): int => ((int) $a->isWrite()) <=> ((int) $b->isWrite())
        );

        foreach ($sortedGoals as $goal) {
            $tools = $goal->toolCandidates();

            if ($goal->isWrite()) {
                $reads = array_values(array_filter(
                    $tools,
                    static fn (string $t): bool => in_array($t, ['FindAvailableSlots', 'SearchCustomer', 'GetCustomerProfile'], true)
                ));
                $writes = array_values(array_filter(
                    $tools,
                    static fn (string $t): bool => ! in_array($t, $reads, true)
                ));
            } else {
                $reads = $tools;
                $writes = [];
            }

            $deps = $allReadTaskIds;

            if ($reads !== []) {
                $readId = TaskId::generate('read');
                $tasks[] = new PlannedTask(
                    $readId,
                    $goal->code(),
                    'Read/prepare for '.$goal->code()->toString(),
                    array_values(array_unique($reads)),
                    $identityTaskId !== null ? [$identityTaskId->toString()] : [],
                    false,
                    false,
                    $order++,
                );
                $allReadTaskIds[] = $readId->toString();
                $deps = $allReadTaskIds;
            }

            if ($writes !== []) {
                $tasks[] = new PlannedTask(
                    TaskId::generate('write'),
                    $goal->code(),
                    'Write step for '.$goal->code()->toString(),
                    array_values(array_unique($writes)),
                    $deps,
                    true,
                    $approvalMap[$goal->code()->toString()] ?? true,
                    $order++,
                );
            } elseif ($reads === [] && $tools !== []) {
                $tasks[] = new PlannedTask(
                    TaskId::generate('step'),
                    $goal->code(),
                    'Execute candidates for '.$goal->code()->toString(),
                    $tools,
                    $deps,
                    $goal->isWrite(),
                    $approvalMap[$goal->code()->toString()] ?? false,
                    $order++,
                );
            }
        }

        return $tasks;
    }

    /**
     * @return array<string, bool>
     */
    private function approvalByGoal(): array
    {
        $map = [];
        foreach ($this->catalog->rules() as $rule) {
            $goal = (string) ($rule['goal'] ?? $rule['code']);
            $map[$goal] = (bool) ($rule['approval'] ?? false);
        }

        return $map;
    }
}
