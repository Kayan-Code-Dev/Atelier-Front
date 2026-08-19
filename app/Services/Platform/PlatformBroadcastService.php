<?php

namespace App\Services\Platform;

use App\Models\Central\PlatformBroadcast;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Services\Mail\PlatformMailService;
use App\Services\Tenant\TenantDatabaseManager;
use App\Services\Tenant\TenantNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class PlatformBroadcastService
{
    public function __construct(
        private readonly TenantDatabaseManager $tenantDatabaseManager,
        private readonly TenantNotifier $tenantNotifier,
        private readonly PlatformMailService $platformMailService,
    ) {}

    /**
     * @param  array{
     *   title: string,
     *   message: string,
     *   target_type: string,
     *   target_plans?: list<string>|null,
     *   target_statuses?: list<string>|null,
     *   channels: list<string>,
     *   priority?: string,
     * }  $payload
     */
    public function send(array $payload, SuperAdmin $sender): PlatformBroadcast
    {
        $tenants = $this->resolveTenants(
            $payload['target_type'],
            $payload['target_plans'] ?? null,
            $payload['target_statuses'] ?? null,
        );

        $broadcast = PlatformBroadcast::query()->create([
            'title' => $payload['title'],
            'message' => $payload['message'],
            'target_type' => $payload['target_type'],
            'target_plans' => $payload['target_plans'] ?? null,
            'target_statuses' => $payload['target_statuses'] ?? null,
            'channels' => $payload['channels'],
            'priority' => $payload['priority'] ?? 'normal',
            'status' => 'sent',
            'target_detail' => $this->buildTargetDetail(
                $payload['target_type'],
                $payload['target_plans'] ?? null,
                $payload['target_statuses'] ?? null,
            ),
            'tenants_targeted' => $tenants->count(),
            'sent_by' => $sender->id,
            'sent_at' => now(),
        ]);

        $delivered = 0;
        $failed = 0;
        $errors = [];
        $channels = $payload['channels'];
        $sendInApp = in_array('inapp', $channels, true);
        $sendEmail = in_array('email', $channels, true);

        foreach ($tenants as $tenant) {
            $tenantOk = false;
            $tenantHadChannel = false;

            if ($sendInApp) {
                $tenantHadChannel = true;
                try {
                    $this->tenantDatabaseManager->connect($tenant);
                    $this->tenantNotifier->broadcast(
                        $payload['title'],
                        $payload['message'],
                        'platform',
                        $payload['priority'] ?? 'normal',
                    );
                    $tenantOk = true;
                } catch (Throwable $exception) {
                    $errors[] = [
                        'tenant_id' => $tenant->id,
                        'tenant_slug' => $tenant->slug,
                        'channel' => 'inapp',
                        'error' => $exception->getMessage(),
                    ];
                }
            }

            if ($sendEmail) {
                $tenantHadChannel = true;
                $emails = $this->platformMailService->resolveTenantEmails($tenant);
                if ($emails === []) {
                    $errors[] = [
                        'tenant_id' => $tenant->id,
                        'tenant_slug' => $tenant->slug,
                        'channel' => 'email',
                        'error' => 'No tenant email found',
                    ];
                } else {
                    $emailOk = true;
                    foreach ($emails as $email) {
                        $error = $this->platformMailService->trySend(
                            fn () => $this->platformMailService->sendPlatformBroadcast(
                                $email,
                                $payload['title'],
                                $payload['message'],
                                (string) $tenant->name,
                            ),
                        );
                        if ($error !== null) {
                            $emailOk = false;
                            $errors[] = [
                                'tenant_id' => $tenant->id,
                                'tenant_slug' => $tenant->slug,
                                'channel' => 'email',
                                'email' => $email,
                                'error' => $error,
                            ];
                        }
                    }
                    if ($emailOk) {
                        $tenantOk = true;
                    }
                }
            }

            if (! $tenantHadChannel) {
                continue;
            }

            if ($tenantOk) {
                $delivered++;
            } else {
                $failed++;
            }
        }

        $broadcast->fill([
            'tenants_delivered' => $delivered,
            'tenants_failed' => $failed,
            'errors' => $errors === [] ? null : $errors,
            'status' => $failed > 0 && $delivered === 0 ? 'failed' : ($failed > 0 ? 'partial' : 'sent'),
        ])->save();

        return $broadcast->load('sender');
    }

    /**
     * @return array{
     *   total_sent: int,
     *   total_delivered: int,
     *   last_7_days: int,
     *   today: int,
     * }
     */
    public function summary(): array
    {
        $base = PlatformBroadcast::query()->whereIn('status', ['sent', 'partial']);

        return [
            'total_sent' => (int) (clone $base)->sum('tenants_delivered'),
            'total_delivered' => (int) (clone $base)->sum('tenants_delivered'),
            'last_7_days' => (int) (clone $base)
                ->where('sent_at', '>=', now()->subDays(7))
                ->count(),
            'today' => (int) (clone $base)
                ->whereDate('sent_at', now()->toDateString())
                ->count(),
        ];
    }

    /**
     * @param  list<string>|null  $plans
     * @param  list<string>|null  $statuses
     * @return Collection<int, Tenant>
     */
    public function resolveTenants(string $targetType, ?array $plans, ?array $statuses): Collection
    {
        $query = Tenant::query()->with('plan');

        if ($targetType === 'by_plan' && is_array($plans) && $plans !== []) {
            $slugs = $this->normalizePlanSlugs($plans);
            $query->whereHas('plan', fn (Builder $builder) => $builder->whereIn('slug', $slugs));
            $query->where('status', 'active');
        } elseif ($targetType === 'by_status' && is_array($statuses) && $statuses !== []) {
            $query->where(function (Builder $builder) use ($statuses): void {
                foreach ($statuses as $status) {
                    $normalized = strtolower(trim($status));
                    if ($normalized === 'active') {
                        $builder->orWhere('status', 'active');
                    } elseif ($normalized === 'suspended') {
                        $builder->orWhereIn('status', ['suspended', 'expired']);
                    } elseif ($normalized === 'trial') {
                        $builder->orWhere(function (Builder $trial): void {
                            $trial->where('status', 'active')
                                ->whereHas('plan', fn (Builder $plan) => $plan->where('price', '<=', 0)
                                    ->orWhere('slug', 'basic'));
                        });
                    }
                }
            });
        } else {
            $query->where('status', 'active');
        }

        return $query->orderBy('id')->get();
    }

    /**
     * @param  list<string>  $plans
     * @return list<string>
     */
    private function normalizePlanSlugs(array $plans): array
    {
        $map = [
            'starter' => 'basic',
            'basic' => 'basic',
            'pro' => 'pro',
            'enterprise' => 'enterprise',
        ];

        return array_values(array_unique(array_filter(array_map(
            fn (string $plan): ?string => $map[strtolower(trim($plan))] ?? strtolower(trim($plan)),
            $plans,
        ))));
    }

    /**
     * @param  list<string>|null  $plans
     * @param  list<string>|null  $statuses
     */
    private function buildTargetDetail(string $targetType, ?array $plans, ?array $statuses): string
    {
        if ($targetType === 'by_plan' && is_array($plans) && $plans !== []) {
            return implode(', ', $plans);
        }

        if ($targetType === 'by_status' && is_array($statuses) && $statuses !== []) {
            return implode(', ', $statuses);
        }

        return 'جميع الأتيليهات النشطة';
    }
}
