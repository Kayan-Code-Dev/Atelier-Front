<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Retrieval;

interface MemoryRetrievalStageInterface
{
    public function name(): MemoryRetrievalStage;

    public function process(MemoryRetrievalBag $bag): void;
}
