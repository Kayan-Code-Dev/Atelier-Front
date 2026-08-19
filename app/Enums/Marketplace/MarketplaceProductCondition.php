<?php

declare(strict_types=1);

namespace App\Enums\Marketplace;

enum MarketplaceProductCondition: string
{
    case Available = 'available';
    case Limited = 'limited';
    case OutOfStock = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'متوفر',
            self::Limited => 'محدود',
            self::OutOfStock => 'نفد',
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
