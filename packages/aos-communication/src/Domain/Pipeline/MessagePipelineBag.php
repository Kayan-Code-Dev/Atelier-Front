<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Pipeline;

use DressnMore\Aos\Communication\Domain\Channel\ChannelType;
use DressnMore\Aos\Communication\Domain\Message\NormalizedMessage;

final class MessagePipelineBag
{
    private ?ChannelType $channel = null;
    private ?NormalizedMessage $message = null;
    private ?string $conversationId = null;
    private bool $outboundSent = false;
    /** @var list<string> */
    private array $stages = [];
    /** @var list<string> */
    private array $errors = [];

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        private readonly array $payload,
        private readonly ?string $tenantId = null,
    ) {}

    /** @return array<string,mixed> */
    public function payload(): array { return $this->payload; }
    public function tenantId(): ?string { return $this->tenantId; }
    public function setChannel(ChannelType $channel): void { $this->channel = $channel; }
    public function channel(): ?ChannelType { return $this->channel; }
    public function setMessage(NormalizedMessage $message): void { $this->message = $message; }
    public function message(): ?NormalizedMessage { return $this->message; }
    public function setConversationId(string $conversationId): void { $this->conversationId = $conversationId; }
    public function conversationId(): ?string { return $this->conversationId; }
    public function markSent(): void { $this->outboundSent = true; }
    public function outboundSent(): bool { return $this->outboundSent; }
    public function mark(string $stage): void { $this->stages[] = $stage; }
    /** @return list<string> */
    public function stages(): array { return $this->stages; }
    public function error(string $error): void { $this->errors[] = $error; }
    /** @return list<string> */
    public function errors(): array { return $this->errors; }
}
