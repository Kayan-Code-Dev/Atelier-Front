<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

enum KnowledgeLifecycleStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Approved = 'approved';
    case Published = 'published';
    case Archived = 'archived';
    case Deprecated = 'deprecated';

    public function isRetrievable(): bool
    {
        return $this === self::Published;
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Review, self::Archived], true),
            self::Review => in_array($next, [self::Approved, self::Draft, self::Archived], true),
            self::Approved => in_array($next, [self::Published, self::Review, self::Archived], true),
            self::Published => in_array($next, [self::Archived, self::Deprecated], true),
            self::Archived => in_array($next, [self::Draft, self::Deprecated], true),
            self::Deprecated => false,
        };
    }
}
