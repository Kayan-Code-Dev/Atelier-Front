<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Infrastructure\Hooks;

use DressnMore\Aos\Tools\Domain\Contracts\ToolAuditHookInterface;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

final class InMemoryAuditHook implements ToolAuditHookInterface
{
    /** @var list<array{tool: string, status: string, correlation: string}> */
    private array $entries = [];

    public function record(ToolRequest $request, ToolManifest $manifest, ToolResult $result): string
    {
        $ref = 'audit_'.bin2hex(random_bytes(6));
        $this->entries[] = [
            'tool' => $manifest->identifier()->toString(),
            'status' => $result->status()->value,
            'correlation' => $request->correlationId()->toString(),
        ];

        return $ref;
    }

    /**
     * @return list<array{tool: string, status: string, correlation: string}>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
