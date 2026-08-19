<?php

declare(strict_types=1);

namespace App\Enums\Marketplace;

enum MarketplacePaymentStatus: string
{
    case Paid = 'paid';
    case Partial = 'partial';
    case Unpaid = 'unpaid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'مدفوع',
            self::Partial => 'جزئي',
            self::Unpaid => 'غير مدفوع',
            self::Refunded => 'مسترد',
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
