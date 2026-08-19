<?php

declare(strict_types=1);

namespace App\Support\AiSales;

enum AiSalesObjection: string
{
    case Price = 'PRICE';
    case AlreadyHaveSystem = 'ALREADY_HAVE_SYSTEM';
    case NeedToThink = 'NEED_TO_THINK';
    case NeedTrial = 'NEED_TRIAL';
    case NeedDemo = 'NEED_DEMO';
    case NotInterested = 'NOT_INTERESTED';
    case LackOfTrust = 'LACK_OF_TRUST';
    case MigrationConcern = 'MIGRATION_CONCERN';
    case TooManyFeatures = 'TOO_MANY_FEATURES';
    case MissingFeature = 'MISSING_FEATURE';
    case PaymentConcern = 'PAYMENT_CONCERN';
    case CustomRequirement = 'CUSTOM_REQUIREMENT';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
