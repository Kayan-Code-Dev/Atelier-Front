<?php

declare(strict_types=1);

namespace App\Enums\Marketplace;

enum MarketplaceOfferStatus: string
{
    case Active = 'active';
    case Scheduled = 'scheduled';
    case Ended = 'ended';
    case Stopped = 'stopped';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Scheduled => 'مجدول',
            self::Ended => 'منتهي',
            self::Stopped => 'متوقف',
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
