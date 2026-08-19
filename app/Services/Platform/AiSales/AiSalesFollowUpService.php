<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Models\Central\CrmFollowUp;
use App\Models\Central\CrmLead;
use App\Support\AiSales\AiSalesChannel;
use App\Support\AiSales\AiSalesEventType;
use App\Support\AiSales\AiSalesFollowUpStatus;
use Carbon\CarbonImmutable;
use RuntimeException;

final class AiSalesFollowUpService
{
    public function __construct(private readonly AiSalesEventPublisher $events) {}

    /**
     * Persistence only — does not send messages.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(CrmLead $lead, array $data, ?int $actorId = null): CrmFollowUp
    {
        $due = CarbonImmutable::parse((string) $data['scheduled_at']);
        $fu = CrmFollowUp::query()->create([
            'lead_id' => $lead->id,
            'conversation_id' => $data['conversation_id'] ?? null,
            'kind' => 'follow_up',
            'channel' => AiSalesChannel::fromStored($data['channel'] ?? $lead->activity)->value,
            'priority' => $data['priority'] ?? 'normal',
            'due_at' => $due,
            'reason' => $data['reason'] ?? $data['message_intent'] ?? null,
            'message_intent' => $data['message_intent'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $actorId,
            'created_by' => $actorId,
            'status' => AiSalesFollowUpStatus::Pending->value,
        ]);

        $lead->next_follow_up_at = $due;
        $lead->save();

        $this->events->publish(AiSalesEventType::FollowUpScheduled, [
            'lead_id' => $lead->id,
            'follow_up_id' => $fu->id,
            'scheduled_at' => $due->toIso8601String(),
            'body' => $fu->reason,
        ], $lead, $actorId);

        return $fu->load(['lead', 'assignee']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CrmFollowUp $fu, array $data): CrmFollowUp
    {
        if (isset($data['status'])) {
            $status = AiSalesFollowUpStatus::tryFrom((string) $data['status']);
            if ($status === null || $status === AiSalesFollowUpStatus::Due) {
                throw new RuntimeException('Invalid follow-up status.');
            }
            $fu->status = $status->value;
            $fu->completed_at = in_array($status, [AiSalesFollowUpStatus::Completed, AiSalesFollowUpStatus::Failed], true)
                ? now()
                : $fu->completed_at;
        }
        if (array_key_exists('due_at', $data) || array_key_exists('scheduled_at', $data)) {
            $fu->due_at = $data['due_at'] ?? $data['scheduled_at'];
        }
        if (array_key_exists('reason', $data)) {
            $fu->reason = $data['reason'];
        }
        if (array_key_exists('message_intent', $data)) {
            $fu->message_intent = $data['message_intent'];
        }
        $fu->save();

        return $fu->fresh(['lead', 'assignee']) ?? $fu;
    }
}
