<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Pipeline;

interface KnowledgeRetrievalStageInterface
{
    public function name(): KnowledgeRetrievalStage;

    public function process(KnowledgeRetrievalBag $bag): void;
}
