<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Registry;

use DressnMore\SmartAssistant\Contracts\Knowledge\KnowledgeProviderInterface;

interface KnowledgeRegistryInterface
{
    public function register(string $id, KnowledgeProviderInterface $provider): void;

    public function get(string $id): ?KnowledgeProviderInterface;
}
