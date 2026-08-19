<?php

declare(strict_types=1);

namespace App\Enums\Marketplace;

enum MarketplaceDiscountType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FreeDelivery = 'free_delivery';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'نسبة مئوية',
            self::Fixed => 'مبلغ ثابت',
            self::FreeDelivery => 'توصيل مجاني',
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
