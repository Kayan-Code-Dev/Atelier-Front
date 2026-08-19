<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Provider;

/**
 * Domain/plugin provider that contributes tools & capabilities to the registry.
 */
final class ProviderDescriptor
{
    /**
     * @param list<string> $domains
     */
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $version,
        private readonly array $domains = [],
        private readonly bool $healthy = true,
    ) {}

    public function id(): string { return $this->id; }
    public function title(): string { return $this->title; }
    public function version(): string { return $this->version; }
    /** @return list<string> */
    public function domains(): array { return $this->domains; }
    public function healthy(): bool { return $this->healthy; }
}
