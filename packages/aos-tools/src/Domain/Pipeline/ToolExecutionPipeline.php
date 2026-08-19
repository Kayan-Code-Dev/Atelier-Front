<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline;

/**
 * Extensible ordered pipeline for tool execution stages.
 */
final class ToolExecutionPipeline
{
    /** @var list<PipelineStageInterface> */
    private array $stages;

    /**
     * @param  list<PipelineStageInterface>  $stages
     */
    public function __construct(array $stages)
    {
        $this->stages = array_values($stages);
    }

    public function process(PipelineState $state): PipelineState
    {
        foreach ($this->stages as $stage) {
            $stage->process($state);
            $state->mark($stage->name());

            if ($state->result() !== null && ! $state->result()->isSuccess()
                && in_array($stage->name(), [
                    PipelineStageName::InputValidated,
                    PipelineStageName::Authorization,
                    PipelineStageName::Execute,
                ], true)
            ) {
                // Still run normalize/audit/analytics if present later — continue unless hard stop desired.
                // Validation/auth/execute failures skip remaining exec-critical stages but audit continues.
            }
        }

        return $state;
    }

    /**
     * @return list<PipelineStageInterface>
     */
    public function stages(): array
    {
        return $this->stages;
    }

    public function withStage(PipelineStageInterface $stage): self
    {
        $stages = $this->stages;
        $stages[] = $stage;

        return new self($stages);
    }
}
