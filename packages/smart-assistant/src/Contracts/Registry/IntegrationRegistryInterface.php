<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Registry;

use DressnMore\SmartAssistant\Domain\Integration\IntegrationProvider;

interface IntegrationRegistryInterface
{
    public function register(IntegrationProvider $provider): void;

    public function get(string $providerId): ?IntegrationProvider;

    /**
     * @return list<IntegrationProvider>
     */
    public function all(): array;
}
