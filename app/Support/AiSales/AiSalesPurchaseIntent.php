<?php

declare(strict_types=1);

namespace App\Support\AiSales;

enum AiSalesPurchaseIntent: string
{
    case High = 'HIGH_PURCHASE_INTENT';
    case Medium = 'MEDIUM_PURCHASE_INTENT';
    case Low = 'LOW_PURCHASE_INTENT';

    public static function fromStored(?string $value): self
    {
        $raw = strtoupper(trim((string) $value));

        return self::tryFrom($raw) ?? match ($raw) {
            'HIGH', 'HIGH_PURCHASE' => self::High,
            'MEDIUM', 'MED' => self::Medium,
            default => self::Low,
        };
    }
}
