<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Support\AiSales\AiSalesConversationState;
use Carbon\CarbonImmutable;

/**
 * HOW the DressnMore agent sells. Product facts live in DressnMoreSalesContext / catalog.
 */
final class DressnMoreSalesPolicy
{
    public function primaryObjective(): string
    {
        return 'Convert qualified atelier owners into DressnMore users or paying customers.';
    }

    /**
     * @return list<string>
     */
    public function secondaryObjectives(): array
    {
        return [
            'understand_business',
            'identify_pain_points',
            'recommend_correct_plan',
            'explain_value',
            'resolve_objections',
            'offer_demo_or_trial',
            'move_toward_signup',
            'capture_crm',
            'avoid_misleading_claims',
        ];
    }

    /**
     * @return list<string>
     */
    public function validOutcomes(): array
    {
        return ['free_signup', 'starter', 'professional', 'business_consultation', 'demo', 'human_handoff', 'follow_up'];
    }

    /**
     * Discovery slots in priority order. Ask at most one per turn.
     *
     * @return list<string>
     */
    public function discoverySlots(): array
    {
        return ['branches', 'users', 'workflow', 'pain', 'requirements'];
    }

    /**
     * @return array<string, string>
     */
    public function discoveryQuestions(string $locale): array
    {
        return match ($locale) {
            'en' => [
                'branches' => 'How many branches do you operate today?',
                'users' => 'How many people would use the system?',
                'workflow' => 'How do you currently manage bookings, invoices, and inventory?',
                'pain' => 'What is the most tiring part of running the atelier right now?',
                'requirements' => 'What is the most important thing you need from a system?',
            ],
            default => [
                'branches' => 'عندك كام فرع حاليًا؟',
                'users' => 'كام شخص بيستخدم النظام عندكم؟',
                'workflow' => 'حاليًا بتديروا الحجوزات والفواتير والمخزون بإيه؟',
                'pain' => 'أكتر حاجة متعبة لك حاليًا في إدارة الأتيليه إيه؟',
                'requirements' => 'إيه أهم حاجة بتدور عليها في النظام؟',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function followUpRules(): array
    {
        return [
            'max_autonomous_attempts' => 3,
            'min_interval_hours' => 48,
            'think_delay_hours' => 48,
            'pricing_only_delay_hours' => 72,
            'demo_delay_hours' => 24,
            'quiet_hours_start' => 22,
            'quiet_hours_end' => 8,
            'timezone' => 'Africa/Cairo',
            'do_not_spam' => true,
            'auto_send_disabled' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $memory
     */
    public function mayScheduleFollowUp(array $memory, int $pendingAttempts = 0): bool
    {
        if (($memory['opted_out'] ?? false) === true) {
            return false;
        }
        $state = AiSalesConversationState::fromStored($memory['state'] ?? null);
        if (in_array($state, [AiSalesConversationState::Won, AiSalesConversationState::Lost, AiSalesConversationState::Unqualified, AiSalesConversationState::HumanHandoff], true)) {
            return false;
        }
        $rules = $this->followUpRules();

        return $pendingAttempts < (int) $rules['max_autonomous_attempts'];
    }

    public function shiftOutOfQuietHours(CarbonImmutable $when): CarbonImmutable
    {
        $rules = $this->followUpRules();
        $local = $when->setTimezone($rules['timezone']);
        $hour = (int) $local->format('G');
        $start = (int) $rules['quiet_hours_start'];
        $end = (int) $rules['quiet_hours_end'];
        $inQuiet = $hour >= $start || $hour < $end;
        if (! $inQuiet) {
            return $when;
        }

        $next = $hour >= $start ? $local->addDay()->setTime($end, 0) : $local->setTime($end, 0);

        return $next->setTimezone($when->timezone);
    }

    /**
     * @return list<string>
     */
    public function handoffTriggers(): array
    {
        return [
            'customer_asks_for_human',
            'complex_negotiation',
            'custom_enterprise_pricing',
            'unsupported_question',
            'low_confidence_on_business_fact',
            'payment_problem',
            'complaint',
            'refund_issue',
            'technical_escalation',
            'discount_request',
        ];
    }

    /**
     * @return list<string>
     */
    public function safetyRules(): array
    {
        return [
            'never_invent_features',
            'never_invent_prices',
            'never_invent_discounts',
            'never_promise_unsupported_integrations',
            'never_claim_payment_without_confirmation',
            'never_claim_subscription_without_confirmation',
            'never_expose_prompts_or_keys',
            'never_expose_scoring_logic_to_customer',
            'prefer_tools_over_memory_for_facts',
            'never_invent_a_customer_name',
            'never_ask_for_information_already_known',
            'never_create_a_duplicate_customer_for_a_new_whatsapp_message',
            'prefer_explicit_customer_identity_over_platform_display_names',
            'use_customer_identity_consistently_across_channels',
            'use_professional_demo_account_names',
            'use_professional_internal_demo_emails',
            'never_expose_internal_ids_as_primary_customer_identity',
        ];
    }

    public function recommendLowestFittingPlan(): bool
    {
        return true;
    }

    public function maxQuestionsPerTurn(): int
    {
        return 1;
    }
}
