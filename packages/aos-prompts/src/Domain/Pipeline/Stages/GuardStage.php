<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Pipeline\Stages;

use DressnMore\Aos\Prompts\Domain\Guard\GuardVerdict;
use DressnMore\Aos\Prompts\Domain\Guard\PromptGuard;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptBag;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptStage;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptStageInterface;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;

final class GuardStage implements PromptStageInterface
{
    public function __construct(
        private readonly PromptGuard $guard,
    ) {}

    public function name(): PromptStage
    {
        return PromptStage::PromptGuard;
    }

    public function process(PromptBag $bag): void
    {
        $result = $this->guard->inspect($bag->request());
        $bag->setGuardResult($result);

        if ($result->verdict() === GuardVerdict::Reject) {
            $bag->reject('prompt_guard: '.implode(',', $result->triggers()));

            return;
        }

        if ($result->verdict() === GuardVerdict::Sanitize && $result->sanitizedMessage() !== null) {
            $req = $bag->request();
            $bag->replaceRequest(PromptBuildRequest::create(
                $result->sanitizedMessage(),
                $req->personaType(),
                $req->templateType(),
                $req->operatingMode(),
                $req->locale(),
                $req->tenantId(),
                $req->tenantInstructions(),
                $req->conversationSummary(),
                $req->conversationContext(),
                $req->planningResult(),
                $req->availableCapabilities(),
                $req->availableTools(),
                $req->attributes(),
                $req->maxTokens(),
                $req->correlationId(),
            ));
        }
    }
}
