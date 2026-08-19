<?php

declare(strict_types=1);

namespace App\Support\AiSales;

enum AiSalesFollowUpStatus: string
{
    case Pending = 'pending';
    case Due = 'due';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    /**
     * @return list<string>
     */
    public static function storedValues(): array
    {
        return [
            self::Pending->value,
            self::Completed->value,
            self::Cancelled->value,
            self::Failed->value,
        ];
    }

    public static function resolve(?string $stored, ?\DateTimeInterface $dueAt, \DateTimeInterface $now): self
    {
        $status = self::tryFrom(strtolower(trim((string) $stored))) ?? self::Pending;
        if ($status === self::Pending && $dueAt !== null && $dueAt <= $now) {
            return self::Due;
        }

        return $status;
    }
}
