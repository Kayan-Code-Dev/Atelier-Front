<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Factory;

use DressnMore\Aos\Communication\Domain\Attachment\Attachment;
use DressnMore\Aos\Communication\Domain\Attachment\AttachmentType;
use DressnMore\Aos\Communication\Domain\Channel\ChannelType;
use DressnMore\Aos\Communication\Domain\Message\MessageId;
use DressnMore\Aos\Communication\Domain\Message\NormalizedMessage;

final class CommunicationFactory
{
    /**
     * @param array<string,mixed> $payload
     */
    public function fromPayload(array $payload): NormalizedMessage
    {
        $attachments = [];
        foreach (($payload['attachments'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $attachments[] = new Attachment(
                AttachmentType::from((string) ($row['type'] ?? AttachmentType::Document->value)),
                (string) ($row['url'] ?? ''),
                isset($row['mime']) ? (string) $row['mime'] : null,
            );
        }

        return new NormalizedMessage(
            MessageId::fromString((string) ($payload['message_id'] ?? MessageId::generate()->toString())),
            (string) ($payload['conversation_id'] ?? ''),
            ChannelType::from((string) ($payload['channel'] ?? ChannelType::WebChat->value)),
            (string) ($payload['sender'] ?? ''),
            (string) ($payload['receiver'] ?? ''),
            (string) ($payload['text'] ?? ''),
            $attachments,
            tenantId: isset($payload['tenant_id']) ? (string) $payload['tenant_id'] : null,
        );
    }
}
