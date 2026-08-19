<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Support\AiSales\AiSalesIntent;

final class AiSalesIntentDetector
{
    /**
     * @return array{intent: AiSalesIntent, signals: array<string, bool>}|null
     */
    public function detect(string $text): ?array
    {
        $hay = mb_strtolower(trim($text));
        if ($hay === '') {
            return null;
        }

        $signals = [
            'greeting' => $this->isGreeting($hay),
            'acknowledgement' => $this->isAcknowledgement($hay),
            'support' => $this->has($hay, ['مشكلة في الحساب', 'مشكلة بالحساب', 'الحساب مش', 'support', 'مش شغال الحساب', 'عندي مشكلة']),
            'asked_price' => $this->has($hay, ['سعر', 'السعر', 'الأسعار', 'الاسعار', 'price', 'cost', 'pricing', 'تكلفة', 'بكام', 'بكم']),
            'asked_plans' => $this->has($hay, ['باقة', 'plan', 'starter', 'professional', 'business', 'free']),
            'needs_plan_fit' => $this->has($hay, ['رشح', 'أنسب باقة', 'انسب باقة', 'باقة تناسب', 'which plan', 'recommend a plan', 'أنسب خطة', 'الباقة المناسبة']),
            'asked_payment' => $this->has($hay, ['دفع', 'payment', 'visa', 'instapay', 'تحويل', 'payment link', 'أشترك', 'اشترك']),
            'asked_demo' => $this->has($hay, ['ديمو', 'demo', 'أحجز عرض', 'احجز عرض', 'عرض عملي']),
            'requested_trial' => $this->has($hay, ['أجرب', 'اجرب', 'تجربة', 'trial', 'عايز أجرب', 'عايز اجرب']),
            'requested_purchase' => $this->has($hay, ['أشتري', 'اشتري', 'اشترك', 'أشترك', 'subscribe', 'buy', 'purchase', 'أبدأ النهاردة', 'start today']),
            'asked_switching' => $this->has($hay, ['أتحول', 'اتحول', 'switch', 'migrate', 'نظام تاني', 'another system', 'برنامج بالفعل']),
            'human_request' => $this->has($hay, ['حد من المبيعات', 'يكلمني', 'إنسان', 'انسان', 'بشري', 'human agent', 'speak to a human', 'sales person', 'talk to a person']),
            'price_objection' => $this->has($hay, ['غالي', 'expensive', 'costly', 'overpriced']),
            'feature_inquiry' => $this->has($hay, ['ميزة', 'مميزات', 'feature', 'features', 'بيعمل إيه', 'بيعمل ايه', 'النظام بيعمل', 'what does it do', 'what can it do', 'موقع', 'website', 'factory', 'مصنع']),
            'need_to_think' => $this->has($hay, ['أفكر', 'افكر', 'خليني أفكر', 'think about', 'ارجعل']),
            'send_details' => $this->has($hay, ['ابعتلي', 'ابعثلي', 'التفاصيل', 'send me details', 'send details']),
            'opt_out' => $this->has($hay, ['وقف رسائل', 'مش عايز رسائل', 'stop messaging', 'unsubscribe', 'opt out']),
        ];

        $intent = match (true) {
            $signals['human_request'] => AiSalesIntent::HumanRequest,
            $signals['opt_out'] => AiSalesIntent::OptOut,
            $signals['support'] => AiSalesIntent::Support,
            $signals['requested_trial'] => AiSalesIntent::TrialRequest,
            $signals['asked_demo'] => AiSalesIntent::DemoRequest,
            $signals['requested_purchase'] => AiSalesIntent::PurchaseIntent,
            $signals['send_details'] => AiSalesIntent::SendDetails,
            $signals['need_to_think'] => AiSalesIntent::NeedToThink,
            $signals['price_objection'] => AiSalesIntent::PriceObjection,
            $signals['asked_switching'] => AiSalesIntent::Comparison,
            $signals['needs_plan_fit'] => AiSalesIntent::PlanRecommendation,
            $signals['asked_price'] => AiSalesIntent::PricingInquiry,
            $signals['feature_inquiry'] || $signals['asked_plans'] => AiSalesIntent::FeatureInquiry,
            $signals['greeting'] => AiSalesIntent::Greeting,
            default => null,
        };

        if ($intent === null) {
            return null;
        }

        return ['intent' => $intent, 'signals' => $signals];
    }

    public function isGreeting(string $hay): bool
    {
        if ($this->has($hay, ['هاي', 'hello', 'hi', 'hey', 'مرحبا', 'مرحباً', 'أهلا', 'اهلا', 'السلام عليكم', 'صباح الخير', 'مساء الخير', 'ازيك', 'إزيك'])) {
            $length = mb_strlen(preg_replace('/\s+/u', '', $hay) ?? $hay);

            return $length <= 40;
        }

        return false;
    }

    public function isAcknowledgement(string $hay): bool
    {
        return in_array($hay, ['تمام', 'ok', 'okay', 'حسنا', 'حسنًا', 'ماشي', 'اوك', 'أوكي', 'okey', 'yes', 'نعم'], true);
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
