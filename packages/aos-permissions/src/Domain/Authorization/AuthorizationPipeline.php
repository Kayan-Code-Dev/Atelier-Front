<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization;

final class AuthorizationPipeline
{
    /** @var list<AuthorizationStageInterface> */
    private array $stages;

    /**
     * @param  list<AuthorizationStageInterface>  $stages
     */
    public function __construct(array $stages)
    {
        $this->stages = array_values($stages);
    }

    public function process(AuthorizationContext $context): AuthorizationContext
    {
        foreach ($this->stages as $stage) {
            $stage->process($context);
            $context->markStage($stage->name()->value);
        }

        return $context;
    }
}
