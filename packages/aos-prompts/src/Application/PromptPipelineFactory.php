<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Application;

use DressnMore\Aos\Prompts\Domain\Composer\PromptComposer;
use DressnMore\Aos\Prompts\Domain\Composer\PromptRenderer;
use DressnMore\Aos\Prompts\Domain\Guard\PromptGuard;
use DressnMore\Aos\Prompts\Domain\Optimizer\PromptOptimizer;
use DressnMore\Aos\Prompts\Domain\Persona\PersonaResolver;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptPipeline;
use DressnMore\Aos\Prompts\Domain\Pipeline\Stages\BuildOptimizeValidateStage;
use DressnMore\Aos\Prompts\Domain\Pipeline\Stages\GuardStage;
use DressnMore\Aos\Prompts\Domain\Pipeline\Stages\ResolvePersonaAndTemplateStage;
use DressnMore\Aos\Prompts\Domain\Policy\PromptPolicyResolver;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptVersionManager;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateRegistryInterface;
use DressnMore\Aos\Prompts\Domain\Validation\PromptValidator;

final class PromptPipelineFactory
{
    public function __construct(
        private readonly PromptGuard $guard,
        private readonly PersonaResolver $personaResolver,
        private readonly PromptPolicyResolver $policyResolver,
        private readonly PromptTemplateRegistryInterface $templates,
        private readonly PromptComposer $composer,
        private readonly PromptOptimizer $optimizer,
        private readonly PromptRenderer $renderer,
        private readonly PromptValidator $validator,
        private readonly PromptVersionManager $versions,
    ) {}

    public function create(): PromptPipeline
    {
        return new PromptPipeline([
            new GuardStage($this->guard),
            new ResolvePersonaAndTemplateStage($this->personaResolver, $this->policyResolver, $this->templates),
            new BuildOptimizeValidateStage(
                $this->composer,
                $this->optimizer,
                $this->renderer,
                $this->validator,
                $this->versions,
            ),
        ]);
    }
}
