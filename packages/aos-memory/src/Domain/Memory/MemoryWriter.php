<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteBag;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWritePipeline;

/**
 * Writes classified memories through the write pipeline.
 */
final class MemoryWriter
{
    public function __construct(
        private readonly MemoryWritePipeline $pipeline,
    ) {}

    public function write(ConversationMemoryUpdate $update): MemoryWriteBag
    {
        return $this->pipeline->process(new MemoryWriteBag($update));
    }
}
