<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Channel;

final class ChannelResolver
{
    public function __construct(private readonly ChannelRegistryInterface $registry) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function resolve(array $payload, ?string $tenantId = null): ?ChannelType
    {
        $raw = strtolower((string) ($payload['channel'] ?? ''));
        foreach (ChannelType::cases() as $type) {
            if ($type->value === $raw && $this->registry->adapter($type, $tenantId) !== null) {
                return $type;
            }
        }

        return null;
    }
}
