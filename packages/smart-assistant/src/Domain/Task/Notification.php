<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Task;

final class Notification
{
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $channel,
        private readonly string $subject,
        private readonly string $body,
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function channel(): string { return $this->channel; }
    public function subject(): string { return $this->subject; }
    public function body(): string { return $this->body; }
}
