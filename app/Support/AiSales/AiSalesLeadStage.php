<?php

declare(strict_types=1);

namespace App\Support\AiSales;

/**
 * Canonical pipeline stored on crm_leads.status.
 * Maps Sprint ENGAGED→contacted; keeps existing CRM values.
 */
enum AiSalesLeadStage: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Interested = 'interested';
    case DemoRequested = 'demo_requested';
    case Trial = 'trial';
    case Quotation = 'quotation';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';
    case NotInterested = 'not_interested';

    public static function fromCrm(?string $status): self
    {
        $raw = strtolower(trim((string) $status));

        return self::tryFrom($raw) ?? match ($raw) {
            'engaged' => self::Contacted,
            'demo_trial', 'demo' => self::DemoRequested,
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

    public function funnelBucket(): string
    {
        return match ($this) {
            self::New, self::Contacted => 'leads',
            self::Qualified => 'qualified',
            self::Interested, self::Quotation, self::Negotiation => 'interested',
            self::DemoRequested, self::Trial => 'demo_trial',
            self::Won => 'paid',
            self::Lost, self::NotInterested => 'leads',
        };
    }
}
