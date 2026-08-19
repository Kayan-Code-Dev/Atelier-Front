<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Registry;

use DressnMore\SmartAssistant\Contracts\Knowledge\KnowledgeProviderInterface;
use DressnMore\SmartAssistant\Contracts\Registry\KnowledgeRegistryInterface;

final class InMemoryKnowledgeRegistry implements KnowledgeRegistryInterface
{
    /** @var array<string, KnowledgeProviderInterface> */
    private array $items = [];

    public function register(string $id, KnowledgeProviderInterface $provider): void
    {
        $this->items[$id] = $provider;
    }

    public function get(string $id): ?KnowledgeProviderInterface
    {
        return $this->items[$id] ?? null;
    }
}
