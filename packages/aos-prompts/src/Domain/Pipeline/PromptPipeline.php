<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Pipeline;

final class PromptPipeline
{
    /** @var list<PromptStageInterface> */
    private array $stages;

    /**
     * @param  list<PromptStageInterface>  $stages
     */
    public function __construct(array $stages)
    {
        $this->stages = array_values($stages);
    }

    public function process(PromptBag $bag): PromptBag
    {
        foreach ($this->stages as $stage) {
            if ($bag->isRejected()) {
                break;
            }
            $stage->process($bag);
            $bag->mark($stage->name()->value);
        }

        return $bag;
    }
}
