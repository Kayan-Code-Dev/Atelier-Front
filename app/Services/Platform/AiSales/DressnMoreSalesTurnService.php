<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Models\Central\CrmFollowUp;
use App\Models\Central\CrmLead;
use App\Models\Central\PlatformAiSalesConversation;
use App\Models\Central\PlatformAiSalesToolAudit;
use App\Services\Platform\AiSales\Identity\AiSalesDemoBindingService;
use App\Services\Platform\AiSales\Identity\CustomerIdentityResolver;
use App\Support\AiSales\AiSalesHandoffState;
use Carbon\CarbonImmutable;

/**
 * Persists a sales-intelligence turn onto existing CRM / conversation tables.
 */
final class DressnMoreSalesTurnService
{
    public function __construct(
        private readonly DressnMoreSalesAgent $agent,
        private readonly DressnMoreSalesPolicy $policy,
        private readonly AiSalesLeadService $leads,
        private readonly AiSalesFollowUpService $followUps,
        private readonly AiSalesConversationService $conversations,
        private readonly CustomerIdentityResolver $identities,
        private readonly AiSalesDemoBindingService $demoBinding,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function run(array $input, ?PlatformAiSalesConversation $conversation = null, ?int $actorId = null): array
    {
        $result = $this->agent->handle($input);
        $lead = $conversation?->lead;

        foreach ($result['tool_actions'] as $action) {
            PlatformAiSalesToolAudit::query()->create([
                'tool' => $action['tool'] ?? 'unknown',
                'lead_id' => $lead?->id,
                'conversation_id' => $conversation?->id,
                'input_summary' => $action['input_summary'] ?? [],
                'result_summary' => $action['result_summary'] ?? [],
                'success' => (bool) ($action['success'] ?? true),
            ]);
        }

        if ($lead instanceof CrmLead) {
            $this->leads->update($lead, $result['lead_updates'] ?? [], $actorId);
            $lead = $lead->fresh() ?? $lead;
            $known = is_array($result['memory']['known'] ?? null) ? $result['memory']['known'] : [];
            $identityMem = is_array($result['memory']['identity'] ?? null) ? $result['memory']['identity'] : [];
            $lead = $this->identities->applyToLead($lead, [
                'customer_name' => $known['customer_name'] ?? $identityMem['customer_name'] ?? null,
                'business_name' => $known['business_name'] ?? $identityMem['business_name'] ?? null,
                'name_source' => $known['name_source'] ?? $identityMem['name_source'] ?? null,
                'name_confidence' => $known['name_confidence'] ?? $identityMem['name_confidence'] ?? null,
                'business_type' => $known['business_type'] ?? $identityMem['business_type'] ?? null,
                'asked_for_name' => $identityMem['asked_for_name'] ?? false,
            ], is_string($identityMem['whatsapp_push_name'] ?? null) ? $identityMem['whatsapp_push_name'] : null);

            $next = (string) ($result['recommended_next_action'] ?? '');
            if (in_array($next, ['trial', 'demo'], true)) {
                $demo = $this->demoBinding->proposeOrReuse($lead, false);
                $result['tool_actions'][] = [
                    'tool' => $demo['reused'] ? 'ReuseDemoAccount' : 'ProposeDemoAccount',
                    'input_summary' => ['lead_id' => $lead->id],
                    'result' => $demo,
                    'result_summary' => $demo,
                    'success' => (bool) ($demo['usable'] ?? false),
                ];
                $result['demo_account'] = $demo;
            }
        }

        if ($conversation instanceof PlatformAiSalesConversation) {
            $conversation->sales_state = $result['state'] ?? $conversation->sales_state;
            $conversation->sales_memory = $result['memory'] ?? $conversation->sales_memory;
            $conversation->intent = $result['intent'] ?? $conversation->intent;
            $conversation->save();

            if (($result['ai_paused'] ?? false) === true) {
                $this->conversations->changeHandoff($conversation, AiSalesHandoffState::HumanRequested, $actorId);
            }
        }

        if ($lead && ($result['recommended_next_action'] ?? '') === 'follow_up' && $this->policy->mayScheduleFollowUp($result['memory'] ?? [], $this->pendingFollowUps($lead))) {
            $hours = (int) $this->policy->followUpRules()['think_delay_hours'];
            foreach ($result['tool_actions'] as $action) {
                if (($action['tool'] ?? '') === 'ScheduleSalesFollowUp' && isset($action['input_summary']['hours'])) {
                    $hours = (int) $action['input_summary']['hours'];
                }
            }
            $when = $this->policy->shiftOutOfQuietHours(CarbonImmutable::now()->addHours($hours));
            $this->followUps->create($lead, [
                'scheduled_at' => $when->toIso8601String(),
                'reason' => 'Sales intelligence follow-up',
                'channel' => $conversation?->channel ?? $lead->activity,
                'conversation_id' => $conversation?->id,
                'message_intent' => $result['intent'] ?? null,
            ], $actorId);
        }

        return $result;
    }

    private function pendingFollowUps(CrmLead $lead): int
    {
        return CrmFollowUp::query()
            ->where('lead_id', $lead->id)
            ->whereNotIn('status', ['completed', 'cancelled', 'failed'])
            ->count();
    }
}
