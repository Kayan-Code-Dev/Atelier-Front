<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Pipeline;

interface PromptStageInterface
{
    public function name(): PromptStage;

    public function process(PromptBag $bag): void;
}
