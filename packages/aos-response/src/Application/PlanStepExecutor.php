<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Planner\Domain\Platform\PlatformExecutionPlan;
use DressnMore\Aos\Response\Domain\Aggregator\ToolOutcome;
use DressnMore\Aos\Tools\Application\ToolGateway;
use DressnMore\Aos\Tools\Domain\Factories\ToolRequestFactory;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;

/**
 * Executes ordered plan steps via Tool Gateway (or demo simulator).
 */
final class PlanStepExecutor
{
    /**
     * @param array<string, array<string, mixed>> $demoPayloads tool => payload for simulator mode
     */
    public function __construct(
        private readonly ?ToolGateway $gateway = null,
        private readonly ToolRequestFactory $requestFactory = new ToolRequestFactory(),
        private readonly ToolOutcomeFactory $outcomes = new ToolOutcomeFactory(),
        private readonly array $demoPayloads = [],
        private readonly bool $simulateWhenUnregistered = true,
    ) {}

    /**
     * @param array<string, mixed> $sharedInput
     * @param list<string> $permissions
     * @param list<string> $capabilities
     * @return list<ToolOutcome>
     */
    public function execute(
        PlatformExecutionPlan $plan,
        array $sharedInput = [],
        array $permissions = ['*'],
        array $capabilities = ['*'],
    ): array {
        $results = [];
        foreach ($plan->orderedSteps() as $step) {
            $tool = $step->toolName();
            $order = $step->order();
            $input = $sharedInput[$tool] ?? $sharedInput;

            if ($this->gateway !== null) {
                try {
                    $request = $this->requestFactory->make(
                        $tool,
                        is_array($input) ? $input : [],
                        ToolOperatingMode::Assistant,
                        $permissions,
                        $capabilities,
                        [],
                        $plan->tenantId(),
                        null,
                        $plan->conversationId(),
                    );
                    $toolResult = $this->gateway->execute($request);
                    $results[] = $this->outcomes->fromToolResult($tool, $toolResult, $order);
                    continue;
                } catch (\Throwable) {
                    if (! $this->simulateWhenUnregistered) {
                        $results[] = $this->outcomes->failure($tool, 'execution_error', 'Tool execution failed', $order);
                        continue;
                    }
                }
            }

            if (isset($this->demoPayloads[$tool])) {
                $results[] = $this->outcomes->success($tool, $this->demoPayloads[$tool], $order);
            } elseif ($this->simulateWhenUnregistered) {
                $results[] = $this->outcomes->success($tool, $this->defaultDemoPayload($tool), $order);
            } else {
                $results[] = $this->outcomes->failure($tool, 'not_found', 'Tool not registered', $order);
            }
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultDemoPayload(string $tool): array
    {
        return match ($tool) {
            'CreateCustomer', 'SearchCustomer' => ['name' => 'سارة أحمد'],
            'CreateReservation' => ['day' => 'الجمعة', 'time' => '5:00 مساءً'],
            'CheckAvailability' => ['available' => true],
            'CancelReservation' => ['cancelled' => true],
            'GenerateReport' => ['amount' => 12450, 'count' => 18],
            'CreateInvoice' => ['invoice' => 'INV-1001'],
            default => ['ok' => true],
        };
    }
}
