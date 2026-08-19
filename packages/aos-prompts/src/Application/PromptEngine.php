<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Application;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Prompts\Contracts\PromptEngineInterface;
use DressnMore\Aos\Prompts\Domain\Composer\PromptComposer;
use DressnMore\Aos\Prompts\Domain\Composer\PromptRenderer;
use DressnMore\Aos\Prompts\Domain\Events\PromptBuilt;
use DressnMore\Aos\Prompts\Domain\Events\PromptGenerationStarted;
use DressnMore\Aos\Prompts\Domain\Events\PromptGuardTriggered;
use DressnMore\Aos\Prompts\Domain\Events\PromptOptimized;
use DressnMore\Aos\Prompts\Domain\Events\PromptRejected;
use DressnMore\Aos\Prompts\Domain\Events\PromptValidated;
use DressnMore\Aos\Prompts\Domain\Events\PromptVersionCreated;
use DressnMore\Aos\Prompts\Domain\Guard\PromptGuard;
use DressnMore\Aos\Prompts\Domain\Optimizer\PromptOptimizer;
use DressnMore\Aos\Prompts\Domain\Persona\PersonaRegistry;
use DressnMore\Aos\Prompts\Domain\Persona\PersonaResolver;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptBag;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptPipeline;
use DressnMore\Aos\Prompts\Domain\Pipeline\Stages\BuildOptimizeValidateStage;
use DressnMore\Aos\Prompts\Domain\Pipeline\Stages\GuardStage;
use DressnMore\Aos\Prompts\Domain\Pipeline\Stages\ResolvePersonaAndTemplateStage;
use DressnMore\Aos\Prompts\Domain\Policy\PromptPolicyResolver;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptDocument;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptRegistry;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptRegistryInterface;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptVersionManager;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateRegistry;
use DressnMore\Aos\Prompts\Domain\Validation\PromptValidator;
use DressnMore\Aos\Prompts\Infrastructure\Bootstrap\BuiltinPromptCatalogBootstrap;
use RuntimeException;

/**
 * Prompt Engine — builds provider-agnostic prompts; never calls an AI provider.
 */
final class PromptEngine implements PromptEngineInterface
{
    public function __construct(
        private readonly PromptPipeline $pipeline,
        private readonly PromptRegistryInterface $registry,
        private readonly EventBusInterface $eventBus,
    ) {}

    public static function createDefault(EventBusInterface $eventBus): self
    {
        $personas = new PersonaRegistry();
        $templates = new PromptTemplateRegistry();
        (new BuiltinPromptCatalogBootstrap($personas, $templates))->seed();

        $pipeline = new PromptPipeline([
            new GuardStage(new PromptGuard()),
            new ResolvePersonaAndTemplateStage(
                new PersonaResolver($personas),
                new PromptPolicyResolver(),
                $templates,
            ),
            new BuildOptimizeValidateStage(
                new PromptComposer(),
                new PromptOptimizer(),
                new PromptRenderer(),
                new PromptValidator(),
                new PromptVersionManager(),
            ),
        ]);

        return new self($pipeline, new PromptRegistry(), $eventBus);
    }

    public function build(PromptBuildRequest $request): PromptDocument
    {
        $this->eventBus->publish(new PromptGenerationStarted($request->correlationId()));

        $bag = new PromptBag($request);
        $this->pipeline->process($bag);

        if ($bag->guardResult() !== null) {
            $this->eventBus->publish(new PromptGuardTriggered(
                $request->correlationId(),
                $bag->guardResult()->verdict()->value,
                $bag->guardResult()->triggers(),
            ));
        }

        if ($bag->isRejected()) {
            $this->eventBus->publish(new PromptRejected(
                $request->correlationId(),
                $bag->rejectionReason(),
            ));
            throw new RuntimeException('Prompt generation rejected: '.$bag->rejectionReason());
        }

        $document = $bag->document();
        if ($document === null) {
            throw new RuntimeException('Prompt pipeline did not produce a document.');
        }

        if ($bag->validation() !== null) {
            $this->eventBus->publish(new PromptValidated(
                $request->correlationId(),
                $bag->validation()->isValid(),
                $bag->validation()->errors(),
                $bag->validation()->warnings(),
            ));
        }

        $this->eventBus->publish(new PromptOptimized(
            $request->correlationId(),
            count($document->sections()),
            $document->tokenBudget()->estimatedTokens(),
        ));

        $this->eventBus->publish(new PromptBuilt(
            $request->correlationId(),
            $document->id()->toString(),
            count($document->sections()),
        ));

        $this->eventBus->publish(new PromptVersionCreated(
            $request->correlationId(),
            $document->id()->toString(),
            $document->version()->version(),
            $document->version()->templateVersion(),
        ));

        $this->registry->register($document);

        return $document;
    }
}
