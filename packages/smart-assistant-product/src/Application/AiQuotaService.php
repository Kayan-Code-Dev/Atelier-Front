<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Central\Tenant;
use App\Support\PlanFeatureGate;
use Carbon\CarbonImmutable;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantQuotaUsage;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Monthly WhatsApp/social assistant message quota, driven by admin plan features.
 * 0 on the plan key = unlimited. Exhausted quota stops auto-replies.
 */
final class AiQuotaService
{
    public const LIMIT_KEY = 'smart_assistant.messages_monthly.max';

    public const FEATURE_KEY = 'smart_assistant.enabled';

    public function __construct(
        private readonly PlanFeatureGate $gate,
    ) {}

    public function period(?CarbonImmutable $at = null): string
    {
        return ($at ?? CarbonImmutable::now())->format('Y-m');
    }

    public function limit(Tenant $tenant): int
    {
        return max(0, $this->gate->limit($tenant, self::LIMIT_KEY));
    }

    public function used(Tenant $tenant, ?string $period = null): int
    {
        $period ??= $this->period();

        try {
            return (int) (SmartAssistantQuotaUsage::query()
                ->where('tenant_id', $tenant->id)
                ->where('period', $period)
                ->value('used_count') ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    public function isUnlimited(Tenant $tenant): bool
    {
        return $this->limit($tenant) <= 0;
    }

    /**
     * Whether the assistant may send another auto-reply.
     */
    public function canConsume(Tenant $tenant): bool
    {
        if ($tenant->isDemo() && $tenant->status === 'active') {
            return true;
        }

        if (! $this->gate->isEnabled($tenant, self::FEATURE_KEY)
            && ! $this->gate->isEnabled($tenant, 'smart_assistant.auto_reply')) {
            return false;
        }

        $limit = $this->limit($tenant);
        if ($limit <= 0) {
            return true;
        }

        return $this->used($tenant) < $limit;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function recordMessage(Tenant $tenant, ?int $conversationId = null, ?int $inboundId = null, array $meta = []): void
    {
        unset($conversationId, $inboundId, $meta);

        try {
            $period = $this->period();
            DB::connection('central')->transaction(function () use ($tenant, $period): void {
                $row = SmartAssistantQuotaUsage::query()->firstOrCreate(
                    [
                        'tenant_id' => (int) $tenant->id,
                        'period' => $period,
                    ],
                    ['used_count' => 0],
                );
                $row->increment('used_count');
            });
        } catch (Throwable) {
            // Never block the inbound path on a counter write.
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Tenant $tenant): array
    {
        $limit = $this->limit($tenant);
        $used = $this->used($tenant);
        $unlimited = $limit <= 0;
        $remaining = $unlimited ? 0 : max(0, $limit - $used);
        $percent = $unlimited ? 0 : (int) min(100, round(($used / max(1, $limit)) * 100));
        $status = 'normal';
        if (! $unlimited) {
            if ($used >= $limit) {
                $status = 'exhausted';
            } elseif ($percent >= 90) {
                $status = 'warning_90';
            } elseif ($percent >= 80) {
                $status = 'warning_80';
            }
        }

        $now = CarbonImmutable::now();

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'percentage' => $percent,
            'reset_at' => $now->endOfMonth()->toIso8601String(),
            'status' => $status,
            'period' => $now->format('Y-m'),
            'unlimited' => $unlimited,
            'enabled' => $this->gate->isEnabled($tenant, self::FEATURE_KEY)
                || $this->gate->isEnabled($tenant, 'smart_assistant.auto_reply')
                || ($tenant->isDemo() && $tenant->status === 'active'),
        ];
    }
}
