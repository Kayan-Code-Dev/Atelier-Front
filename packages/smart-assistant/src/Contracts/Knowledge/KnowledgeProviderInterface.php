<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Knowledge;

interface KnowledgeProviderInterface
{
    /**
     * @return list<array{id:string,title:string}>
     */
    public function search(string $tenantId, string $query): array;
}
