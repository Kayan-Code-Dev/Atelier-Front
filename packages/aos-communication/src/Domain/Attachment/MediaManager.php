<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Attachment;

final class MediaManager
{
    public function normalizeMime(?string $mimeType): ?string
    {
        if ($mimeType === null) {
            return null;
        }

        return strtolower(trim($mimeType));
    }
}
