<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Pipeline;

interface MemoryWriteStageInterface
{
    public function name(): MemoryWriteStage;

    public function process(MemoryWriteBag $bag): void;
}
