<?php

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformBroadcastResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sender = $this->relationLoaded('sender') ? $this->sender : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'target_type' => $this->target_type,
            'target_plans' => $this->target_plans ?? [],
            'target_statuses' => $this->target_statuses ?? [],
            'target_detail' => $this->target_detail,
            'channels' => $this->channels ?? [],
            'priority' => $this->priority,
            'status' => $this->status,
            'sent_to' => (int) $this->tenants_delivered,
            'tenants_targeted' => (int) $this->tenants_targeted,
            'tenants_delivered' => (int) $this->tenants_delivered,
            'tenants_failed' => (int) $this->tenants_failed,
            'sent_by' => $sender ? [
                'id' => $sender->id,
                'name' => $sender->name,
                'email' => $sender->email,
            ] : null,
            'sent_at' => $this->sent_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
