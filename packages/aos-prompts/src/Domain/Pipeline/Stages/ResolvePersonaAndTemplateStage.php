<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Pipeline\Stages;

use DressnMore\Aos\Prompts\Domain\Persona\PersonaResolver;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptBag;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptStage;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptStageInterface;
use DressnMore\Aos\Prompts\Domain\Policy\PromptPolicyResolver;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateRegistryInterface;

final class ResolvePersonaAndTemplateStage implements PromptStageInterface
{
    public function __construct(
        private readonly PersonaResolver $personaResolver,
        private readonly PromptPolicyResolver $policyResolver,
        private readonly PromptTemplateRegistryInterface $templates,
    ) {}

    public function name(): PromptStage
    {
        return PromptStage::PersonaResolver;
    }

    public function process(PromptBag $bag): void
    {
        $bag->mark(PromptStage::PlanningResult->value);
        $bag->setPersona($this->personaResolver->resolve($bag->request()));

        $templateType = $this->policyResolver->resolveTemplateType($bag->request());
        $bag->setTemplate($this->templates->getByType($templateType));
        $bag->mark(PromptStage::OperatingModeResolver->value);
        $bag->mark(PromptStage::TenantInstructions->value);
        $bag->mark(PromptStage::BusinessRules->value);
    }
}
