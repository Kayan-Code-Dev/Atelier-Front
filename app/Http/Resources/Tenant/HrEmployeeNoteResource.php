<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrEmployeeNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'at' => $this->created_at?->toIso8601String(),
            'author' => $this->author_name ?: ($this->author?->name ?? 'نظام'),
            'type' => $this->type,
            'content' => $this->content,
        ];
    }
}
