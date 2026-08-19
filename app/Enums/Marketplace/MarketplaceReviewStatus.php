<?php

declare(strict_types=1);

namespace App\Enums\Marketplace;

enum MarketplaceReviewStatus: string
{
    case Published = 'published';
    case Hidden = 'hidden';
    case AwaitingReply = 'awaiting_reply';

    public function label(): string
    {
        return match ($this) {
            self::Published => 'منشور',
            self::Hidden => 'مخفي',
            self::AwaitingReply => 'بانتظار الرد',
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
