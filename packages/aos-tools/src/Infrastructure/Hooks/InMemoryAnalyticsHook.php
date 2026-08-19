<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Infrastructure\Hooks;

use DressnMore\Aos\Tools\Domain\Contracts\ToolAnalyticsHookInterface;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

final class InMemoryAnalyticsHook implements ToolAnalyticsHookInterface
{
    /** @var list<array{tool: string, status: string}> */
    private array $entries = [];

    public function record(ToolRequest $request, ToolManifest $manifest, ToolResult $result): string
    {
        $ref = 'analytics_'.bin2hex(random_bytes(6));
        $this->entries[] = [
            'tool' => $manifest->identifier()->toString(),
            'status' => $result->status()->value,
        ];

        return $ref;
    }

    /**
     * @return list<array{tool: string, status: string}>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
