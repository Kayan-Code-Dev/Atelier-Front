<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Provider;

use DressnMore\Aos\Ai\Contracts\AiProviderInterface;

interface ProviderRegistryInterface
{
    public function register(ProviderDescriptor $descriptor, AiProviderInterface $plugin): void;

    public function get(ProviderId $id): ?ProviderDescriptor;

    public function plugin(ProviderId $id): ?AiProviderInterface;

    /** @return list<ProviderDescriptor> */
    public function all(): array;

    public function update(ProviderDescriptor $descriptor): void;
}
