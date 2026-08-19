<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Events;

use DateTimeImmutable;

final class CommunicationDomainEvent
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

    public static function channelRegistered(array $payload = []): self { return new self('ChannelRegistered', $payload); }
    public static function channelConnected(array $payload = []): self { return new self('ChannelConnected', $payload); }
    public static function channelDisconnected(array $payload = []): self { return new self('ChannelDisconnected', $payload); }
    public static function messageReceived(array $payload = []): self { return new self('MessageReceived', $payload); }
    public static function messageNormalized(array $payload = []): self { return new self('MessageNormalized', $payload); }
    public static function messageSent(array $payload = []): self { return new self('MessageSent', $payload); }
    public static function messageDelivered(array $payload = []): self { return new self('MessageDelivered', $payload); }
    public static function messageRead(array $payload = []): self { return new self('MessageRead', $payload); }
    public static function messageFailed(array $payload = []): self { return new self('MessageFailed', $payload); }
    public static function attachmentUploaded(array $payload = []): self { return new self('AttachmentUploaded', $payload); }
    public static function conversationRouted(array $payload = []): self { return new self('ConversationRouted', $payload); }
    public static function commentClassified(array $payload = []): self { return new self('CommentClassified', $payload); }
    public static function privateConversationStarted(array $payload = []): self { return new self('PrivateConversationStarted', $payload); }
}
