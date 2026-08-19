<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Response;

use DressnMore\Aos\Planner\Domain\Platform\PlatformExecutionPlan;
use DressnMore\Aos\Response\Domain\Aggregator\AggregatedToolResults;

/**
 * Full AI Core cycle result: plan + tool outcomes + final reply.
 */
final class EndToEndResult
{
    public function __construct(
        private readonly PlatformExecutionPlan $plan,
        private readonly AggregatedToolResults $toolResults,
        private readonly FinalAiResponse $response,
    ) {}

    public function plan(): PlatformExecutionPlan { return $this->plan; }
    public function toolResults(): AggregatedToolResults { return $this->toolResults; }
    public function response(): FinalAiResponse { return $this->response; }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'plan' => $this->plan->toArray(),
            'tools' => [
                'count' => $this->toolResults->count(),
                'succeeded' => count($this->toolResults->succeeded()),
                'failed' => count($this->toolResults->failed()),
            ],
            'response' => $this->response->toArray(),
        ];
    }
}
