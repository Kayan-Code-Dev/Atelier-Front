<?php

declare(strict_types=1);

namespace App\Enums\Marketplace;

enum MarketplaceMessageAuthor: string
{
    case Customer = 'customer';
    case Store = 'store';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
