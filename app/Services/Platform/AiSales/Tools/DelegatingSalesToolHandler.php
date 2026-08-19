<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales\Tools;

use Closure;
use DressnMore\Aos\Tools\Domain\Contracts\BusinessToolHandlerInterface;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ConceptualSchema;
use DressnMore\Aos\Tools\Domain\Tool\ToolCategoryCode;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;
use DressnMore\Aos\Tools\Domain\Tool\ToolRiskLevel;
use DressnMore\Aos\Tools\Domain\Tool\ToolVersion;
use Throwable;

/**
 * Thin AOS wrapper. Business logic stays on DressnMoreSalesTools / CRM services.
 */
final class DelegatingSalesToolHandler implements BusinessToolHandlerInterface
{
    /**
     * @param  list<string>  $required
     * @param  Closure(array<string, mixed>): array<string, mixed>  $execute
     */
    public function __construct(
        private readonly string $id,
        private readonly string $description,
        private readonly array $required,
        private readonly Closure $execute,
        private readonly ToolRiskLevel $risk = ToolRiskLevel::Low,
    ) {}

    public function identifier(): ToolIdentifier
    {
        return ToolIdentifier::fromString($this->id);
    }

    public function manifest(): ToolManifest
    {
        $properties = [];
        foreach ($this->required as $field) {
            $properties[$field] = ['type' => 'string'];
        }

        return new ToolManifest(
            identifier: $this->identifier(),
            version: ToolVersion::v1(),
            category: ToolCategoryCode::custom('sales'),
            description: $this->description,
            capabilities: ['ai_sales.tools'],
            permissions: ['ai_sales.manage'],
            operatingModes: [
                ToolOperatingMode::Assistant,
                ToolOperatingMode::Hybrid,
                ToolOperatingMode::FullAuto,
            ],
            riskLevel: $this->risk,
            supportedIntents: ['sales.dressnmore'],
            inputSchema: ConceptualSchema::of([
                'type' => 'object',
                'required' => $this->required,
                'properties' => $properties,
            ]),
            outputSchema: ConceptualSchema::of(['type' => 'object']),
        );
    }

    public function execute(ToolRequest $request): ToolResult
    {
        try {
            $payload = ($this->execute)($request->input());

            return ToolResult::success(is_array($payload) ? $payload : ['value' => $payload]);
        } catch (Throwable $exception) {
            return ToolResult::failed([]);
        }
    }
}
