<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Message;

final class MessageValidator
{
    /**
     * @return list<string>
     */
    public function validate(NormalizedMessage $message): array
    {
        $errors = [];
        if ($message->conversationId() === '') { $errors[] = 'missing_conversation_id'; }
        if ($message->sender() === '') { $errors[] = 'missing_sender'; }
        if ($message->receiver() === '') { $errors[] = 'missing_receiver'; }
        if ($message->text() === '' && $message->attachments() === []) { $errors[] = 'empty_content'; }

        return $errors;
    }
}
