<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Events;

use DateTimeImmutable;

final class TenantAiDomainEvent
{
    /**
     * @param array<string, scalar|null> $payload
     */
    public function __construct(
        private readonly string $name,
        private readonly array $payload = [],
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {}

    public function name(): string { return $this->name; }
    /** @return array<string, scalar|null> */
    public function payload(): array { return $this->payload; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }

    public static function workspaceCreated(array $p = []): self { return new self('WorkspaceCreated', $p); }
    public static function workspaceUpdated(array $p = []): self { return new self('WorkspaceUpdated', $p); }
    public static function conversationStarted(array $p = []): self { return new self('ConversationStarted', $p); }
    public static function conversationClosed(array $p = []): self { return new self('ConversationClosed', $p); }
    public static function messageReceived(array $p = []): self { return new self('MessageReceived', $p); }
    public static function messageGenerated(array $p = []): self { return new self('MessageGenerated', $p); }
    public static function toolRequested(array $p = []): self { return new self('ToolRequested', $p); }
    public static function toolExecuted(array $p = []): self { return new self('ToolExecuted', $p); }
    public static function toolExecutionFailed(array $p = []): self { return new self('ToolExecutionFailed', $p); }
}
