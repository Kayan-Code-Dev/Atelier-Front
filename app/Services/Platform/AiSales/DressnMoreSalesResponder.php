<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Support\AiSales\AiSalesConversationState;
use App\Support\AiSales\AiSalesObjection;

/**
 * Policy-based sales copy. Prices and features must be injected from tools — never invented.
 */
final class DressnMoreSalesResponder
{
    /**
     * @param  array<string, mixed>  $ctx
     */
    public function compose(string $locale, string $mode, array $ctx): string
    {
        $en = $locale === 'en';
        $plan = (string) ($ctx['plan_name'] ?? $ctx['plan'] ?? '');
        $price = $ctx['price_label'] ?? null;
        $question = (string) ($ctx['question'] ?? '');
        $cta = (string) ($ctx['cta'] ?? '');
        if (! in_array($mode, ['qualify_plan', 'discovery'], true)) {
            $question = '';
        }

        $body = match ($mode) {
            'handoff' => $en
                ? 'Understood — I will connect you with a DressnMore sales specialist now. I will pause here so they can continue with you.'
                : 'تمام، هوصّلك بحد من فريق مبيعات DressnMore دلوقتي. هوقف الرد التلقائي عشان يكمل معاك.',
            'checkout' => $en
                ? 'Great — you can start from the DressnMore plan request / checkout flow. I will not keep asking discovery questions.'
                : 'تمام — تقدر تبدأ الاشتراك من طلب الباقة / الدفع في DressnMore. مش هسألك أسئلة زيادة.',
            'trial' => $en
                ? 'We can start a short demo-tenant trial so you can try the system on your own atelier.'
                : 'ممكن نبدأ تجربة قصيرة (حساب تجريبي) عشان تجرب النظام على الأتيليه بتاعك.',
            'demo' => $en
                ? 'We can arrange a walkthrough of the modules that match your atelier.'
                : 'ممكن نرتب عرض عملي للموديولات اللي تناسب شغلك.',
            'price_objection' => $en
                ? 'I hear that the price feels high. Help me understand the size of the atelier so we recommend the lowest plan that actually fits — I will not invent a discount.'
                : 'فاهم إن السعر حاسس إنه غالي. خليني أفهم حجم الشغل الأول عشان نرشح أقل باقة مناسبة فعلًا — والسعر بيتحسب من كتالوج الباقات فقط.',
            'competitor' => $en
                ? 'What do you like most about the system you already use, and what feels missing?'
                : 'إيه أكتر حاجة عاجباك في النظام الحالي؟ وإيه الحاجة اللي حاسس إنها ناقصة؟',
            'think' => $en
                ? 'Of course — take the time you need. I will leave a follow-up on our side, without spamming you.'
                : 'أكيد، خد وقتك. هسيب متابعة بسيطة عندنا من غير ما نكرر الرسائل.',
            'unknown_feature' => $en
                ? 'I cannot confirm that capability from the DressnMore feature catalog. I will not guess — a specialist can confirm if you want.'
                : 'ماقدرش أأكد الميزة دي من كتالوج DressnMore. مش هخمّن — لو حابب حد من الفريق يؤكدها.',
            'details' => $this->details($en, $ctx),
            'recommend' => $this->recommend($en, $plan, $price, $ctx),
            'value' => $en
                ? 'Instead of tracking bookings, invoices, and inventory across files, DressnMore brings them into one atelier workspace so you can see the work from one place.'
                : 'بدل ما تتابع الحجوزات والفواتير والمخزون على أكتر من ملف، DressnMore يجمعهم في نظام واحد ويخليك تعرف حالة الشغل من مكان واحد.',
            'greeting' => $this->greeting($en, $ctx),
            'support' => $en
                ? 'I can help with the account issue — tell me what you are seeing and I will route you to the right person if needed.'
                : 'أكيد، خلينا نتابع مشكلة الحساب. قولّي اللي ظاهر عندك وأقدر أحولك لحد من الفريق لو احتاجنا.',
            'qualify_plan' => $en
                ? 'I can recommend the lowest plan that actually fits once I know the atelier size.'
                : 'أقدر أرشح أقل باقة مناسبة لحجم الأتيليه بعد ما أعرف الحجم.',
            'discovery' => '',
            default => $en
                ? 'I am here to understand the atelier and recommend the right DressnMore plan — without guessing prices or features.'
                : 'موجود عشان أفهم الأتيليه وأرشح باقة DressnMore المناسبة — من غير تخمين في السعر أو المميزات.',
        };

        $parts = array_values(array_filter([$body, $question, $cta], static fn (string $p): bool => trim($p) !== ''));
        if (($ctx['ask_name'] ?? false) === true) {
            $parts[] = $en
                ? 'By the way, what should I call you?'
                : 'على فكرة، أحب أنادي حضرتك بإيه؟ 😊';
        }

        return implode("\n\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function greeting(bool $en, array $ctx): string
    {
        $name = is_string($ctx['customer_name'] ?? null) ? trim($ctx['customer_name']) : '';
        $greetedBefore = (bool) ($ctx['greeted_before'] ?? false);
        if ($name === '') {
            if ($greetedBefore) {
                return $en ? 'Welcome back.' : 'أهلاً، نورتنا.';
            }

            return $en
                ? 'Welcome to DressnMore — an atelier system for bookings, invoices, inventory, and tailoring. Would you like pricing, a feature overview, or a demo?'
                : 'أهلاً بيك في DressnMore. نظام لإدارة الأتيليهات: حجوزات وفواتير ومخزون وتفصيل. تحب تعرف الأسعار، ولا المميزات، ولا نرتب ديمو؟';
        }
        if ($greetedBefore) {
            return $en ? 'Hello '.$name.'.' : 'أهلاً يا '.$name.' 👋';
        }

        return $en
            ? 'Hello '.$name.' — welcome to DressnMore, an atelier system for bookings, invoices, inventory, and tailoring. Would you like pricing, a feature overview, or a demo?'
            : 'أهلاً يا '.$name.' 👋 نورتنا. نظام DressnMore لإدارة الأتيليهات: حجوزات وفواتير ومخزون وتفصيل. تحب تعرف الأسعار، ولا المميزات، ولا نرتب ديمو؟';
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function recommend(bool $en, string $plan, mixed $price, array $ctx): string
    {
        $priceBit = is_string($price) && $price !== '' ? " ({$price})" : '';
        $reasons = is_array($ctx['reasons'] ?? null) ? array_slice($ctx['reasons'], 0, 4) : [];
        $reasonText = $reasons === [] ? '' : ($en ? ' Because: '.implode(', ', $reasons).'.' : ' لأن: '.implode('، ', $reasons).'.');
        $alt = (string) ($ctx['alternative'] ?? '');
        $altBit = $alt === '' ? '' : ($en
            ? " If multi-branch or extra modules are not required, {$alt} can be enough."
            : " ولو متطلب الفروع/الموديولات الإضافية مش أساسي، باقة {$alt} ممكن تكفي.");

        if ($en) {
            return 'Based on what you shared, the lowest fitting plan is '.$plan.$priceBit.'.'.$reasonText.$altBit;
        }

        return 'بناءً على اللي قلته، أقل باقة مناسبة هي '.$plan.$priceBit.'.'.$reasonText.$altBit;
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function details(bool $en, array $ctx): string
    {
        $plan = (string) ($ctx['plan_name'] ?? $ctx['plan'] ?? 'the matching plan');
        $benefits = is_array($ctx['benefits'] ?? null) ? array_slice($ctx['benefits'], 0, 5) : [];
        $list = $benefits === [] ? '' : "\n- ".implode("\n- ", $benefits);

        return $en
            ? "Here is a short {$plan} summary, focused on your atelier:{$list}\nIf useful, we can start a demo or trial next."
            : "ملخص سريع لباقة {$plan} حسب شغلك:{$list}\nلو مناسب، نقدر نبدأ ديمو أو تجربة.";
    }

    public function objectionMode(AiSalesObjection $objection): string
    {
        return match ($objection) {
            AiSalesObjection::Price => 'price_objection',
            AiSalesObjection::AlreadyHaveSystem => 'competitor',
            AiSalesObjection::NeedToThink => 'think',
            AiSalesObjection::NeedTrial => 'trial',
            AiSalesObjection::NeedDemo => 'demo',
            AiSalesObjection::MissingFeature => 'unknown_feature',
            default => 'value',
        };
    }

    public function cta(string $locale, AiSalesConversationState $state): string
    {
        $en = $locale === 'en';

        return match ($state) {
            AiSalesConversationState::Checkout => $en ? 'You can proceed to checkout when you are ready.' : 'تقدر تكمل للدفع لما تكون جاهز.',
            AiSalesConversationState::Trial => $en ? 'Shall I start a trial for you?' : 'تحب نبدأ التجربة؟',
            AiSalesConversationState::DemoRequested => $en ? 'Shall I arrange the demo?' : 'تحب نرتب العرض؟',
            default => '',
        };
    }
}
