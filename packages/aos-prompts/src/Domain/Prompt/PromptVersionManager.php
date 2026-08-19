<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Prompt;

/**
 * Manages prompt document versioning metadata.
 */
final class PromptVersionManager
{
    public function __construct(
        private readonly string $engineVersion = '0.7.0',
    ) {}

    public function next(string $templateVersion = '1.0.0'): PromptVersion
    {
        return PromptVersion::create(
            version: $this->engineVersion,
            generatedBy: 'aos.prompts',
            templateVersion: $templateVersion,
        );
    }
}
