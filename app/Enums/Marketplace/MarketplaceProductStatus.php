<?php

declare(strict_types=1);

namespace App\Enums\Marketplace;

enum MarketplaceProductStatus: string
{
    case Draft = 'draft';
    case Unpublished = 'unpublished';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Unpublished => 'غير منشور',
            self::Published => 'منشور',
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
