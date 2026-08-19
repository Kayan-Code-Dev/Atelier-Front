<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Response\Domain\Aggregator\ToolOutcome;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;

/**
 * Adapts Gateway ToolResult into response-layer ToolOutcome.
 */
final class ToolOutcomeFactory
{
    public function fromToolResult(string $toolName, ToolResult $result, int $order = 0): ToolOutcome
    {
        $errors = [];
        foreach ($result->errors() as $error) {
            $errors[] = [
                'code' => $error->code(),
                'message' => $error->message(),
            ];
        }

        return new ToolOutcome(
            $toolName,
            $result->isSuccess(),
            $result->payload(),
            $result->warnings(),
            $errors,
            $result->status()->value,
            $order,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function success(string $toolName, array $payload = [], int $order = 0): ToolOutcome
    {
        return new ToolOutcome($toolName, true, $payload, [], [], 'success', $order);
    }

    public function failure(string $toolName, string $code, string $message, int $order = 0): ToolOutcome
    {
        return new ToolOutcome($toolName, false, [], [], [['code' => $code, 'message' => $message]], 'failed', $order);
    }
}
