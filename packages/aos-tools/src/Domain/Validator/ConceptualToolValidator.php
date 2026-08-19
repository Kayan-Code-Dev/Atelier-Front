<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Validator;

use DressnMore\Aos\Tools\Domain\Contracts\ToolValidatorInterface;
use DressnMore\Aos\Tools\Domain\Policies\ToolModePolicy;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ToolFailure;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

/**
 * Conceptual input + mode validation (no JSON Schema engine).
 */
final class ConceptualToolValidator implements ToolValidatorInterface
{
    public function __construct(
        private readonly ToolModePolicy $modePolicy = new ToolModePolicy(),
    ) {}

    public function validate(ToolRequest $request, ToolManifest $manifest): array
    {
        $failures = [];

        if (! $this->modePolicy->isAllowed($manifest, $request->executionContext()->operatingMode())) {
            $failures[] = ToolFailure::of(
                'MODE_NOT_ALLOWED',
                sprintf(
                    'Tool [%s] does not support mode [%s].',
                    $manifest->identifier()->toString(),
                    $request->executionContext()->operatingMode()->value
                )
            );
        }

        foreach ($manifest->inputSchema()->requiredFields() as $field) {
            if (! array_key_exists($field, $request->input())) {
                $failures[] = ToolFailure::of(
                    'MISSING_INPUT',
                    sprintf('Required input field [%s] is missing.', $field)
                );
            }
        }

        return $failures;
    }
}
