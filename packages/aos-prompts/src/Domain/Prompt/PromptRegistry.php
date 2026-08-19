<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Prompt;

final class PromptRegistry implements PromptRegistryInterface
{
    /** @var array<string, PromptDocument> */
    private array $items = [];

    public function register(PromptDocument $document): void
    {
        $this->items[$document->id()->toString()] = $document;
    }

    public function get(PromptId $id): ?PromptDocument
    {
        return $this->items[$id->toString()] ?? null;
    }

    public function all(): array
    {
        return array_values($this->items);
    }
}
