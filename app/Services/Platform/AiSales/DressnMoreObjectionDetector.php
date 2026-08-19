<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Support\AiSales\AiSalesObjection;

final class DressnMoreObjectionDetector
{
    public function detect(string $text): ?AiSalesObjection
    {
        $hay = mb_strtolower(trim($text));
        if ($hay === '') {
            return null;
        }

        return match (true) {
            $this->has($hay, ['خصم', 'discount', '40%', 'تسعير خاص', 'سعر خاص']) => AiSalesObjection::CustomRequirement,
            $this->has($hay, ['غالي', 'expensive', 'costly', 'overpriced', '$40 غالي']) => AiSalesObjection::Price,
            $this->has($hay, ['عندي نظام', 'عندي برنامج', 'برنامج بالفعل', 'نظام تاني', 'another system', 'already use', 'already have']) => AiSalesObjection::AlreadyHaveSystem,
            $this->has($hay, ['أفكر', 'افكر', 'خليني أفكر', 'think about', "i'll think", 'ارجعل']) => AiSalesObjection::NeedToThink,
            $this->has($hay, ['أجرب', 'تجربة', 'trial']) => AiSalesObjection::NeedTrial,
            $this->has($hay, ['ديمو', 'demo', 'عرض عملي']) => AiSalesObjection::NeedDemo,
            $this->has($hay, ['مش مهتم', 'not interested', 'لا شكرا', 'no thanks']) => AiSalesObjection::NotInterested,
            $this->has($hay, ['مش راضي', 'complaint', 'سيء', 'نصب']) => AiSalesObjection::LackOfTrust,
            $this->has($hay, ['ترحيل', 'migrate', 'نقل البيانات', 'migration']) => AiSalesObjection::MigrationConcern,
            $this->has($hay, ['معقد', 'كتير أوي', 'too many', 'complicated']) => AiSalesObjection::TooManyFeatures,
            $this->has($hay, ['ناقص', 'مش موجود', 'missing', "don't have", 'مافيش']) => AiSalesObjection::MissingFeature,
            $this->has($hay, ['دفع', 'فيزا', 'payment issue', 'البطاقة']) && $this->has($hay, ['مشكلة', 'فشل', 'مش شغال', 'failed']) => AiSalesObjection::PaymentConcern,
            default => null,
        };
    }

    /**
     * @param  list<string>  $needles
     */
    private function has(string $hay, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && mb_strpos($hay, mb_strtolower($needle)) !== false) {
                return true;
            }
        }

        return false;
    }
}
