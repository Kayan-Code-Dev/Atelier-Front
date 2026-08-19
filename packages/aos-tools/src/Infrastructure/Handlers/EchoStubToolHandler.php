<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Infrastructure\Handlers;

use DressnMore\Aos\Tools\Domain\Contracts\BusinessToolHandlerInterface;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ConceptualSchema;
use DressnMore\Aos\Tools\Domain\Tool\ToolCategory;
use DressnMore\Aos\Tools\Domain\Tool\ToolCategoryCode;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;
use DressnMore\Aos\Tools\Domain\Tool\ToolRiskLevel;
use DressnMore\Aos\Tools\Domain\Tool\ToolVersion;

/**
 * Test/demo stub only — not a DressnMore business tool.
 */
final class EchoStubToolHandler implements BusinessToolHandlerInterface
{
    public function identifier(): ToolIdentifier
    {
        return ToolIdentifier::fromString('EchoStub');
    }

    public function manifest(): ToolManifest
    {
        return new ToolManifest(
            identifier: $this->identifier(),
            version: ToolVersion::v1(),
            category: ToolCategoryCode::fromEnum(ToolCategory::Administration),
            description: 'Sprint 4 stub handler that echoes input (no business logic).',
            capabilities: ['tools.echo'],
            permissions: ['tools.echo.execute'],
            operatingModes: [
                ToolOperatingMode::Assistant,
                ToolOperatingMode::Hybrid,
                ToolOperatingMode::FullAuto,
            ],
            riskLevel: ToolRiskLevel::Low,
            supportedIntents: ['system.ping'],
            inputSchema: ConceptualSchema::of([
                'type' => 'object',
                'required' => ['message'],
                'properties' => [
                    'message' => ['type' => 'string'],
                ],
            ]),
            outputSchema: ConceptualSchema::of([
                'type' => 'object',
                'properties' => [
                    'echo' => ['type' => 'string'],
                ],
            ]),
        );
    }

    public function execute(ToolRequest $request): ToolResult
    {
        /** @var mixed $message */
        $message = $request->input()['message'] ?? '';

        return ToolResult::success([
            'echo' => is_scalar($message) ? (string) $message : '',
        ]);
    }
}
