<?php

declare(strict_types=1);

namespace App\Support\AiSales;

enum AiSalesIntent: string
{
    case PricingInquiry = 'pricing_inquiry';
    case FeatureInquiry = 'feature_inquiry';
    case PlanRecommendation = 'plan_recommendation';
    case DemoRequest = 'demo_request';
    case TrialRequest = 'trial_request';
    case PurchaseIntent = 'purchase_intent';
    case Support = 'support';
    case Comparison = 'comparison';
    case PriceObjection = 'price_objection';
    case FeatureObjection = 'feature_objection';
    case HumanRequest = 'human_request';
    case Greeting = 'greeting';
    case NeedToThink = 'need_to_think';
    case SendDetails = 'send_details';
    case OptOut = 'opt_out';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public function suggestedStage(): ?AiSalesLeadStage
    {
        return match ($this) {
            self::DemoRequest => AiSalesLeadStage::DemoRequested,
            self::TrialRequest => AiSalesLeadStage::Trial,
            self::PurchaseIntent => AiSalesLeadStage::Negotiation,
            self::NeedToThink => AiSalesLeadStage::Quotation,
            self::PricingInquiry, self::FeatureInquiry, self::Comparison, self::SendDetails => AiSalesLeadStage::Interested,
            self::PlanRecommendation => AiSalesLeadStage::Qualified,
            self::OptOut => AiSalesLeadStage::Lost,
            default => null,
        };
    }
}
