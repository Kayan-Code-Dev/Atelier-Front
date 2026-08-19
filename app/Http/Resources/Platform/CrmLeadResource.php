<?php

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Central\CrmLead */
class CrmLeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'facebook' => $this->facebook,
            'atelier_name' => $this->atelier_name,
            'governorate' => $this->governorate,
            'activity' => $this->activity,
            'branches_count' => $this->branches_count,
            'employees_count' => $this->employees_count,
            'source' => $this->source,
            'score' => $this->score,
            'temperature' => $this->temperature,
            'importance' => $this->importance ?: $this->temperature,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'assigned_name' => $this->assignee?->name,
            'last_contact_at' => optional($this->last_contact_at)?->toIso8601String(),
            'next_follow_up_at' => optional($this->next_follow_up_at)?->toIso8601String(),
            'last_message' => $this->last_message,
            'read_at' => optional($this->read_at)?->toIso8601String(),
            'is_read' => $this->read_at !== null,
            'offer_value' => (float) $this->offer_value,
            'expected_plan' => $this->expected_plan,
            'close_probability' => $this->close_probability,
            'tenant_id' => $this->tenant_id,
            'notes' => $this->notes,
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'title' => $e->title,
                'body' => $e->body,
                'author' => $e->author?->name,
                'created_at' => optional($e->created_at)?->toIso8601String(),
            ])->values()),
            'lead_notes' => $this->whenLoaded('leadNotes', fn () => $this->leadNotes->map(fn ($n) => [
                'id' => $n->id,
                'body' => $n->body,
                'author' => $n->author?->name,
                'created_at' => optional($n->created_at)?->toIso8601String(),
            ])->values()),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'url' => $a->url,
                'created_at' => optional($a->created_at)?->toIso8601String(),
            ])->values()),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
