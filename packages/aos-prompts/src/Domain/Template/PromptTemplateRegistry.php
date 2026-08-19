<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Template;

final class PromptTemplateRegistry implements PromptTemplateRegistryInterface
{
    /** @var array<string, PromptTemplate> */
    private array $byId = [];

    /** @var array<string, string> */
    private array $byType = [];

    public function register(PromptTemplate $template): void
    {
        $this->byId[$template->id()->toString()] = $template;
        $this->byType[$template->type()->value] = $template->id()->toString();
    }

    public function get(PromptTemplateId $id): ?PromptTemplate
    {
        return $this->byId[$id->toString()] ?? null;
    }

    public function getByType(PromptTemplateType $type): ?PromptTemplate
    {
        $id = $this->byType[$type->value] ?? null;
        if ($id === null) {
            return null;
        }

        return $this->byId[$id] ?? null;
    }

    public function all(): array
    {
        return array_values($this->byId);
    }
}
