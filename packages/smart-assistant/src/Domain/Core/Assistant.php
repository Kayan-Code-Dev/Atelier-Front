<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Core;

/** Domain model — no persistence. */
final class Assistant
{
    /**
     * @param list<string> $enabledAgentIds
     * @param list<string> $enabledChannelIds
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $name,
        private readonly string $status = 'active',
        private readonly array $enabledAgentIds = [],
        private readonly array $enabledChannelIds = [],
        private readonly string $version = '1.0.0',
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function name(): string { return $this->name; }
    public function status(): string { return $this->status; }
    /** @return list<string> */
    public function enabledAgentIds(): array { return $this->enabledAgentIds; }
    /** @return list<string> */
    public function enabledChannelIds(): array { return $this->enabledChannelIds; }
    public function version(): string { return $this->version; }
}
