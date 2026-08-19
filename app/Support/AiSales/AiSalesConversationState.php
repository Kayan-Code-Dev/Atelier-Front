<?php

declare(strict_types=1);

namespace App\Support\AiSales;

/**
 * DressnMore sales-intelligence FSM.
 * Maps onto canonical CRM {@see AiSalesLeadStage} — does not replace it.
 */
enum AiSalesConversationState: string
{
    case New = 'NEW';
    case Discovery = 'DISCOVERY';
    case Qualification = 'QUALIFICATION';
    case Recommendation = 'RECOMMENDATION';
    case Objection = 'OBJECTION';
    case Consideration = 'CONSIDERATION';
    case DemoRequested = 'DEMO_REQUESTED';
    case Trial = 'TRIAL';
    case Checkout = 'CHECKOUT';
    case Won = 'WON';
    case Lost = 'LOST';
    case HumanHandoff = 'HUMAN_HANDOFF';
    case Unqualified = 'UNQUALIFIED';

    public static function fromStored(?string $value): self
    {
        $raw = strtoupper(trim((string) $value));

        return self::tryFrom($raw) ?? match (strtolower((string) $value)) {
            'new' => self::New,
            'contacted', 'engaged' => self::Discovery,
            'qualified' => self::Qualification,
            'interested' => self::Recommendation,
            'quotation' => self::Consideration,
            'demo_requested', 'demo' => self::DemoRequested,
            'trial' => self::Trial,
            'negotiation' => self::Checkout,
            'won' => self::Won,
            'lost' => self::Lost,
            'not_interested' => self::Unqualified,
            'human_handoff' => self::HumanHandoff,
            default => self::New,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public function toLeadStage(): AiSalesLeadStage
    {
        return match ($this) {
            self::New => AiSalesLeadStage::New,
            self::Discovery => AiSalesLeadStage::Contacted,
            self::Qualification => AiSalesLeadStage::Qualified,
            self::Recommendation, self::Objection => AiSalesLeadStage::Interested,
            self::Consideration => AiSalesLeadStage::Quotation,
            self::DemoRequested => AiSalesLeadStage::DemoRequested,
            self::Trial => AiSalesLeadStage::Trial,
            self::Checkout => AiSalesLeadStage::Negotiation,
            self::Won => AiSalesLeadStage::Won,
            self::Lost => AiSalesLeadStage::Lost,
            self::Unqualified => AiSalesLeadStage::NotInterested,
            self::HumanHandoff => AiSalesLeadStage::Interested,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Won, self::Lost, self::Unqualified], true);
    }
}
