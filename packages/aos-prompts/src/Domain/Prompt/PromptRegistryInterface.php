<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Prompt;

interface PromptRegistryInterface
{
    public function register(PromptDocument $document): void;

    public function get(PromptId $id): ?PromptDocument;

    /**
     * @return list<PromptDocument>
     */
    public function all(): array;
}
