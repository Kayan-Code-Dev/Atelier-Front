<?php

declare(strict_types=1);

namespace App\Enums\Marketplace;

enum MarketplaceOrderStatus: string
{
    case New = 'new';
    case Processing = 'processing';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديدة',
            self::Processing => 'قيد المعالجة',
            self::Ready => 'جاهزة',
            self::Completed => 'مكتملة',
            self::Cancelled => 'ملغاة',
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
