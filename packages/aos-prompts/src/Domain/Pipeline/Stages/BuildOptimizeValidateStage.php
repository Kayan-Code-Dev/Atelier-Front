<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Pipeline\Stages;

use DressnMore\Aos\Prompts\Domain\Composer\PromptComposer;
use DressnMore\Aos\Prompts\Domain\Composer\PromptRenderer;
use DressnMore\Aos\Prompts\Domain\Optimizer\PromptOptimizer;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptBag;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptStage;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptStageInterface;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptDocument;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptId;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptVersionManager;
use DressnMore\Aos\Prompts\Domain\Prompt\TokenBudget;
use DressnMore\Aos\Prompts\Domain\Validation\PromptValidator;

final class BuildOptimizeValidateStage implements PromptStageInterface
{
    public function __construct(
        private readonly PromptComposer $composer,
        private readonly PromptOptimizer $optimizer,
        private readonly PromptRenderer $renderer,
        private readonly PromptValidator $validator,
        private readonly PromptVersionManager $versions,
    ) {}

    public function name(): PromptStage
    {
        return PromptStage::PromptReady;
    }

    public function process(PromptBag $bag): void
    {
        if ($bag->persona() === null) {
            $bag->reject('missing_persona');

            return;
        }

        $bag->mark(PromptStage::ConversationContext->value);
        $bag->mark(PromptStage::ConversationSummary->value);
        $bag->mark(PromptStage::MemoryContext->value);
        $bag->mark(PromptStage::KnowledgeContext->value);
        $bag->mark(PromptStage::ToolConstraints->value);
        $bag->mark(PromptStage::SafetyPolicies->value);
        $bag->mark(PromptStage::LocalizationRules->value);
        $bag->mark(PromptStage::FormattingRules->value);

        $sections = $this->composer->compose($bag->request(), $bag->persona(), $bag->template());
        $sections = $this->optimizer->optimize($sections);
        $sections = $this->optimizer->compress($sections);
        $bag->setSections($sections);
        $bag->mark(PromptStage::PromptOptimization->value);

        $rendered = $this->renderer->render($sections);
        $bag->setRendered($rendered);

        $budget = TokenBudget::estimateFromText($rendered, $bag->request()->maxTokens());
        $validation = $this->validator->validate($sections, $bag->persona(), $budget);
        $bag->setValidation($validation);
        $bag->mark(PromptStage::PromptValidation->value);

        if (! $validation->isValid()) {
            $bag->reject('validation: '.implode(',', $validation->errors()));

            return;
        }

        $templateVersion = $bag->template()?->version() ?? '1.0.0';
        $document = new PromptDocument(
            PromptId::generate(),
            $this->versions->next($templateVersion),
            $sections,
            $rendered,
            $budget,
            [
                'correlation_id' => $bag->request()->correlationId(),
                'persona' => $bag->persona()->type()->value,
                'template' => $bag->template()?->type()->value,
                'tenant_id' => $bag->request()->tenantId(),
                'warnings' => implode('|', $validation->warnings()),
            ],
        );
        $bag->setDocument($document);
    }
}
