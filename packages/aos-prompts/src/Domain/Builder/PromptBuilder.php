<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Builder;

use DressnMore\Aos\Prompts\Domain\Composer\PromptComposer;
use DressnMore\Aos\Prompts\Domain\Composer\PromptRenderer;
use DressnMore\Aos\Prompts\Domain\Optimizer\PromptOptimizer;
use DressnMore\Aos\Prompts\Domain\Persona\Persona;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptDocument;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptId;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptVersionManager;
use DressnMore\Aos\Prompts\Domain\Prompt\TokenBudget;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplate;
use DressnMore\Aos\Prompts\Domain\Validation\PromptValidator;
use DressnMore\Aos\Prompts\Domain\Validation\ValidationResult;
use RuntimeException;

/**
 * Imperative builder for prompts outside the full pipeline (tests / adapters).
 */
final class PromptBuilder
{
    private ?ValidationResult $lastValidation = null;

    public function __construct(
        private readonly PromptComposer $composer = new PromptComposer(),
        private readonly PromptOptimizer $optimizer = new PromptOptimizer(),
        private readonly PromptRenderer $renderer = new PromptRenderer(),
        private readonly PromptValidator $validator = new PromptValidator(),
        private readonly PromptVersionManager $versions = new PromptVersionManager(),
    ) {}

    public function build(
        PromptBuildRequest $request,
        Persona $persona,
        ?PromptTemplate $template = null,
    ): PromptDocument {
        $sections = $this->composer->compose($request, $persona, $template);
        $sections = $this->optimizer->optimize($sections);
        $sections = $this->optimizer->compress($sections);
        $rendered = $this->renderer->render($sections);
        $budget = TokenBudget::estimateFromText($rendered, $request->maxTokens());
        $this->lastValidation = $this->validator->validate($sections, $persona, $budget);

        if (! $this->lastValidation->isValid()) {
            throw new RuntimeException('Prompt validation failed: '.implode(',', $this->lastValidation->errors()));
        }

        return new PromptDocument(
            PromptId::generate(),
            $this->versions->next($template?->version() ?? '1.0.0'),
            $sections,
            $rendered,
            $budget,
            [
                'correlation_id' => $request->correlationId(),
                'persona' => $persona->type()->value,
                'template' => $template?->type()->value,
                'tenant_id' => $request->tenantId(),
            ],
        );
    }

    public function lastValidation(): ?ValidationResult
    {
        return $this->lastValidation;
    }
}
