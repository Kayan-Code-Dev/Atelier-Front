<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Template;

interface PromptTemplateRegistryInterface
{
    public function register(PromptTemplate $template): void;

    public function get(PromptTemplateId $id): ?PromptTemplate;

    public function getByType(PromptTemplateType $type): ?PromptTemplate;

    /**
     * @return list<PromptTemplate>
     */
    public function all(): array;
}
