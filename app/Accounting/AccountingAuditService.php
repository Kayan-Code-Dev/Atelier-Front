<?php

namespace App\Accounting;

use App\Models\Tenant\AccountingAuditLog;
use Illuminate\Support\Facades\Schema;

class AccountingAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(?int $userId, string $action, string $entityType, int $entityId, array $metadata = []): void
    {
        if (! Schema::connection('tenant')->hasTable('accounting_audit_logs')) {
            return;
        }

        AccountingAuditLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timeline(string $entityType, int $entityId): array
    {
        if (! Schema::connection('tenant')->hasTable('accounting_audit_logs')) {
            return [];
        }

        return AccountingAuditLog::query()
            ->with('user:id,name')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('id')
            ->get()
            ->map(fn (AccountingAuditLog $log): array => [
                'action' => $log->action,
                'user_id' => $log->user_id,
                'user_name' => $log->user?->name,
                'timestamp' => $log->created_at?->toIso8601String(),
                'metadata' => $log->metadata,
            ])
            ->all();
    }
}
