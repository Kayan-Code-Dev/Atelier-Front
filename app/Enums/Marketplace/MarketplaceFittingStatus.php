<?php

declare(strict_types=1);

namespace App\Enums\Marketplace;

enum MarketplaceFittingStatus: string
{
    case Upcoming = 'upcoming';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'قادمة',
            self::Confirmed => 'مؤكدة',
            self::Completed => 'مكتملة',
            self::Cancelled => 'ملغاة',
            self::NoShow => 'لم يحضر',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
