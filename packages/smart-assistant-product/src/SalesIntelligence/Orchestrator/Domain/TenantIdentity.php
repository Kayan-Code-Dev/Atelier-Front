<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\SalesIntelligence\Orchestrator\Domain;

/**
 * Resolved WhatsApp AI identity for one tenant connection.
 * Platform vs atelier persona — never mixed in prompts.
 */
final class TenantIdentity
{
    public function __construct(
        public readonly int $tenantId,
        public readonly bool $isPlatform,
        public readonly string $businessName,
        public readonly ?string $agentName,
        public readonly string $agentRole,
        public readonly string $tone,
        public readonly string $style,
        public readonly string $language,
        public readonly ?string $personality,
        public readonly ?string $businessInstructions,
        public readonly ?string $welcomeMessage,
        public readonly ?string $handoffMessage,
        public readonly ?string $departmentName = null,
    ) {}

    /** Arabic intro line for the assistant (no invented personal name). */
    public function introLineAr(): string
    {
        if ($this->isPlatform) {
            $name = $this->agentName ?? 'سارة';

            return "أنت «{$name}»، المساعد الذكي لمنصة «DressnMore» — منصة إدارة الأتيليهات (SaaS) — وتردين على واتساب المنصة.";
        }

        $business = $this->businessName !== '' ? $this->businessName : 'الأتيليه';
        $team = $this->teamLabelAr();
        $dept = $this->departmentName !== null && $this->departmentName !== ''
            ? 'قسم '.$this->departmentName
            : $team;
        if ($this->agentName !== null && $this->agentName !== '') {
            return "أنتِ «{$this->agentName}» من {$dept} في «{$business}» وتردين على واتساب العملاء.";
        }

        return "معك {$team} «{$business}» على واتساب.";
    }

    /** Customer-facing greeting the agent must use on first contact. */
    public function greetingLineAr(): string
    {
        $business = $this->businessName !== '' ? $this->businessName : 'الأتيليه';
        $team = $this->teamLabelAr();
        $from = $this->departmentName !== null && $this->departmentName !== ''
            ? 'قسم '.$this->departmentName
            : $team;
        if ($this->agentName !== null && $this->agentName !== '') {
            return "أنا {$this->agentName} من {$from} {$business}";
        }

        return "معك {$team} {$business}";
    }

    public function teamLabelAr(): string
    {
        return match ($this->agentRole) {
            'support' => 'فريق خدمة العملاء',
            'reservations' => 'فريق الحجوزات',
            'customer_success' => 'فريق نجاح العملاء',
            default => 'فريق مبيعات',
        };
    }

    /** Explicit English guardrail block for OpenAI. */
    public function llmGuardrailEn(): string
    {
        if ($this->isPlatform) {
            return 'You represent DressnMore, the SaaS platform for atelier management. '
                .'Never claim to be an atelier employee. Never mention rental dresses or atelier inventory unless the customer asks about the platform product itself.';
        }

        return 'You represent the tenant business ("'.$this->businessName.'"), NOT DressnMore. '
            .'DressnMore is only the software platform provider behind the scenes. '
            .'Never introduce yourself as DressnMore unless the customer explicitly asks about the software platform itself. '
            .'Speak as a representative of '.$this->businessName.'.';
    }

    public function roleLabelAr(): string
    {
        return match ($this->agentRole) {
            'sales' => 'موظفة مبيعات',
            'support' => 'خدمة عملاء',
            'reservations' => 'موظفة حجوزات',
            'customer_success' => 'نجاح العملاء',
            default => 'مساعدة',
        };
    }

    /** @return array<string, mixed> */
    public function toPromptArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'is_platform' => $this->isPlatform,
            'business_name' => $this->businessName,
            'agent_name' => $this->agentName,
            'agent_role' => $this->agentRole,
            'tone' => $this->tone,
            'style' => $this->style,
            'language' => $this->language,
            'intro_line_ar' => $this->introLineAr(),
            'guardrail_en' => $this->llmGuardrailEn(),
        ];
    }
}
