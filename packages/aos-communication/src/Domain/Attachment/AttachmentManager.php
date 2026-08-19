<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Attachment;

final class AttachmentManager
{
    public function isValid(Attachment $attachment): bool
    {
        if ($attachment->url() === '') {
            return false;
        }

        if ($attachment->sizeBytes() !== null && $attachment->sizeBytes() < 0) {
            return false;
        }

        return true;
    }
}
