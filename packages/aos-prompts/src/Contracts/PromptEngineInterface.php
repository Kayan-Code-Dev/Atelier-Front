<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Contracts;

use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptDocument;

/**
 * Port: build a provider-agnostic prompt document.
 */
interface PromptEngineInterface
{
    public function build(PromptBuildRequest $request): PromptDocument;
}
