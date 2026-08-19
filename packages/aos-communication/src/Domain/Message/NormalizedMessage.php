<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Message;

use DateTimeImmutable;
use DressnMore\Aos\Communication\Domain\Attachment\Attachment;
use DressnMore\Aos\Communication\Domain\Channel\ChannelType;

final class NormalizedMessage
{
    /**
     * @param list<Attachment> $attachments
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(
        private readonly MessageId $id,
        private readonly string $conversationId,
        private readonly ChannelType $channel,
        private readonly string $sender,
        private readonly string $receiver,
        private readonly string $text,
        private readonly array $attachments = [],
        private readonly ?string $location = null,
        private readonly ?string $replyReference = null,
        private readonly ?string $reaction = null,
        private readonly array $metadata = [],
        private readonly DateTimeImmutable $timestamp = new DateTimeImmutable(),
        private readonly ?string $tenantId = null,
    ) {}

    public function id(): MessageId { return $this->id; }
    public function conversationId(): string { return $this->conversationId; }
    public function channel(): ChannelType { return $this->channel; }
    public function sender(): string { return $this->sender; }
    public function receiver(): string { return $this->receiver; }
    public function text(): string { return $this->text; }
    /** @return list<Attachment> */
    public function attachments(): array { return $this->attachments; }
    public function location(): ?string { return $this->location; }
    public function replyReference(): ?string { return $this->replyReference; }
    public function reaction(): ?string { return $this->reaction; }
    /** @return array<string, scalar|null> */
    public function metadata(): array { return $this->metadata; }
    public function timestamp(): DateTimeImmutable { return $this->timestamp; }
    public function tenantId(): ?string { return $this->tenantId; }
}
