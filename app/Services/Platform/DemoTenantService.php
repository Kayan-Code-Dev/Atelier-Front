<?php

namespace App\Services\Platform;

use App\Models\Central\Payment;
use App\Models\Central\Plan;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use App\Services\Mail\PlatformMailService;
use App\Services\Tenant\TenantDatabaseManager;
use App\Services\Tenant\TenantNotifier;
use App\Support\PlanCurrency;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DemoTenantService
{
    public function __construct(
        private readonly TenantProvisioningService $tenantProvisioningService,
        private readonly PlatformMailService $platformMailService,
        private readonly TenantDatabaseManager $tenantDatabaseManager,
        private readonly TenantNotifier $tenantNotifier,
    ) {}

    /**
     * @param  array{search?:string|null, status?:string|null, lifecycle?:string|null}  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $now = CarbonImmutable::now();
        $lifecycle = trim((string) ($filters['lifecycle'] ?? 'active'));
        if ($lifecycle !== 'expired') {
            $lifecycle = 'active';
        }

        $query = Tenant::query()
            ->with(['plan', 'domains'])
            ->where('metadata->source', 'demo')
            ->latest('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $wildcard = '%'.mb_strtolower($search).'%';
            $query->where(function ($builder) use ($wildcard): void {
                $builder->whereRaw('LOWER(name) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(slug) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(database_name) LIKE ?', [$wildcard]);
            });
        }

        if ($lifecycle === 'expired') {
            // Ended demos: marked expired, or past subscription end (cron lag).
            $query->where(function ($builder) use ($now): void {
                $builder->where('status', 'expired')
                    ->orWhere(function ($inner) use ($now): void {
                        $inner->whereNotNull('subscription_ends_at')
                            ->where('subscription_ends_at', '<', $now);
                    });
            });
        } else {
            // Active demo list: hide anything that belongs on the expired page.
            $query->where('status', '!=', 'expired')
                ->where(function ($builder) use ($now): void {
                    $builder->whereNull('subscription_ends_at')
                        ->orWhere('subscription_ends_at', '>=', $now);
                });

            $status = trim((string) ($filters['status'] ?? ''));
            if ($status !== '' && $status !== 'expired') {
                $query->where('status', $status);
            }
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a demo tenant with no commercial plan, provision DB, seed admin, attach domain.
     *
     * @param  array{name: string, email: string, password: string, days: int, phone?: string|null}  $data
     * @param  array{id?: int|null, name?: string|null, email?: string|null}|null  $createdBy
     * @return array{tenant: Tenant, admin: array{email: string, password: string}, login_url: string, hostname_label: string}
     */
    public function createAndProvision(array $data, ?array $createdBy = null): array
    {
        $days = max(1, min(7, (int) ($data['days'] ?? 7)));
        $email = strtolower(trim((string) $data['email']));
        $password = (string) $data['password'];
        $name = trim((string) $data['name']);
        $phone = trim((string) ($data['phone'] ?? ''));
        $adminName = trim((string) ($data['admin_name'] ?? $name));

        $startsAt = CarbonImmutable::now();
        $endsAt = $startsAt->addDays($days)->endOfDay();

        $metadata = [
            'source' => 'demo',
            'demo_days' => $days,
            'admin_email' => $email,
            'admin_name' => $adminName,
            'phone' => $phone !== '' ? $phone : null,
        ];
        if (! empty($data['lead_id'])) {
            $metadata['crm_lead_id'] = (int) $data['lead_id'];
        }

        $signupChannel = trim((string) ($data['signup_channel'] ?? ''));
        if ($signupChannel !== '') {
            $metadata['signup_channel'] = $signupChannel;
        }

        if ($createdBy !== null) {
            if (! empty($createdBy['id'])) {
                $metadata['created_by_admin_id'] = (int) $createdBy['id'];
            }
            if (! empty($createdBy['name'])) {
                $metadata['created_by_admin_name'] = (string) $createdBy['name'];
            }
            if (! empty($createdBy['email'])) {
                $metadata['created_by_admin_email'] = strtolower(trim((string) $createdBy['email']));
            }
        }

        $tenant = $this->tenantProvisioningService->create([
            'name' => $name,
            'plan_id' => null,
            'subscription_starts_at' => $startsAt,
            'subscription_ends_at' => $endsAt,
            'metadata' => $metadata,
        ]);

        try {
            $tenant = $this->tenantProvisioningService->migrate($tenant);
        } catch (Throwable $exception) {
            throw new RuntimeException('فشل تهيئة حساب الديمو: '.$exception->getMessage(), 0, $exception);
        }

        $hostnameLabel = $this->primaryHostname((string) $tenant->slug);
        try {
            $this->tenantProvisioningService->addDomain($tenant, $hostnameLabel);
        } catch (Throwable $exception) {
            Log::warning('Demo tenant domain attach failed', [
                'tenant_id' => $tenant->id,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            $credentials = $this->tenantProvisioningService->seedAdmin($tenant, [
                'email' => $email,
                'password' => $password,
                'admin_name' => $adminName,
                'phone' => $phone,
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('فشل إنشاء مستخدم الديمو: '.$exception->getMessage(), 0, $exception);
        }

        $loginHostname = $hostnameLabel;
        if (
            str_contains((string) config('app.url'), 'staging')
            || app()->environment('staging')
            || str_contains(base_path(), 'back-staging')
            || str_contains(base_path(), 'staging')
        ) {
            $loginHostname = 'staging-tenant.dressnmore.it.com';
        } else {
            // Shared tenant portal — slug subdomains are not a reliable login host.
            $frontendUrl = rtrim((string) (env('FRONTEND_URL') ?: 'https://dressnmore.it.com'), '/');
            $loginHostname = preg_replace('#^https?://#i', '', $frontendUrl) ?: 'dressnmore.it.com';
        }

        $loginUrl = 'https://'.preg_replace('#^https?://#i', '', $loginHostname);
        $loginUrl = rtrim($loginUrl, '/').'/login';

        $tenant = $tenant->refresh()->load(['plan', 'domains']);

        return [
            'tenant' => $tenant,
            'admin' => [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ],
            'login_url' => $loginUrl,
            'hostname_label' => $hostnameLabel,
            'demo_days' => $days,
            'subscription_ends_at' => $tenant->subscription_ends_at?->toISOString(),
        ];
    }

    /**
     * Mark expired active tenants and notify them once.
     *
     * @return array{expired: int, notified: int}
     */
    public function processExpiry(): array
    {
        $now = CarbonImmutable::now();
        $expired = 0;
        $notified = 0;

        $tenants = Tenant::query()
            ->where('status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', $now)
            ->orderBy('id')
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->status = 'expired';
            $tenant->save();
            $expired++;

            $metadata = is_array($tenant->metadata) ? $tenant->metadata : [];
            if (! empty($metadata['expiry_notified_at'])) {
                continue;
            }

            if ($this->notifyExpired($tenant)) {
                $metadata['expiry_notified_at'] = $now->toISOString();
                $tenant->metadata = $metadata;
                $tenant->save();
                $notified++;
            }
        }

        return ['expired' => $expired, 'notified' => $notified];
    }

    public function destroy(Tenant $tenant): void
    {
        $metadata = is_array($tenant->metadata) ? $tenant->metadata : [];
        if (($metadata['source'] ?? null) !== 'demo') {
            throw new RuntimeException('يمكن حذف الحسابات التجريبية فقط من هذه الشاشة.');
        }

        $this->tenantProvisioningService->destroy($tenant);
    }

    /**
     * Promote a demo tenant to a paid/commercial subscription.
     *
     * @param  array{
     *   plan_id:int,
     *   starts_at?:string|null,
     *   ends_at?:string|null,
     *   mark_as_paid?:bool|null,
     *   amount?:float|int|string|null,
     *   payment_method?:string|null,
     *   payment_reference?:string|null,
     *   payment_notes?:string|null
     * }  $data
     * @return array{tenant: Tenant, subscription: Subscription, payment: Payment|null}
     */
    public function promoteToSubscription(Tenant $tenant, array $data): array
    {
        $metadata = is_array($tenant->metadata) ? $tenant->metadata : [];
        if (($metadata['source'] ?? null) !== 'demo') {
            throw new RuntimeException('هذا الحساب ليس تجريبياً.');
        }

        $plan = Plan::query()
            ->whereKey((int) $data['plan_id'])
            ->where('status', 'active')
            ->first();
        if (! $plan instanceof Plan) {
            throw new RuntimeException('الباقة غير موجودة أو غير مفعلة.');
        }

        $startsAt = ! empty($data['starts_at'])
            ? CarbonImmutable::parse((string) $data['starts_at'])
            : CarbonImmutable::now();

        $endsAt = ! empty($data['ends_at'])
            ? CarbonImmutable::parse((string) $data['ends_at'])
            : $startsAt->addDays(max(1, (int) ($plan->duration_days ?? 30)));

        if ($endsAt->lte($startsAt)) {
            throw new RuntimeException('تاريخ نهاية الاشتراك يجب أن يكون بعد تاريخ البداية.');
        }

        /** @var array{tenant:Tenant, subscription:Subscription, payment:Payment|null} $result */
        $result = DB::connection('central')->transaction(function () use ($tenant, $metadata, $plan, $startsAt, $endsAt, $data): array {
            $tenant->plan_id = $plan->id;
            $tenant->subscription_starts_at = $startsAt;
            $tenant->subscription_ends_at = $endsAt;
            $tenant->status = 'active';

            // Move the account out of the demo bucket so it appears in ateliers list.
            unset($metadata['source'], $metadata['demo_days'], $metadata['expiry_notified_at']);
            $metadata['converted_from_demo'] = true;
            $metadata['converted_at'] = CarbonImmutable::now()->toISOString();
            $metadata['converted_plan_id'] = $plan->id;
            $tenant->metadata = $metadata;
            $tenant->save();

            Subscription::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            $subscription = Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $payment = null;
            if ((bool) ($data['mark_as_paid'] ?? true)) {
                $amount = array_key_exists('amount', $data) && $data['amount'] !== null
                    ? (float) $data['amount']
                    : (float) $plan->price;

                $payment = Payment::query()->create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'purpose' => 'demo_conversion',
                    'amount' => $amount,
                    'currency' => PlanCurrency::normalize($plan->currency ?? 'EGP'),
                    'method' => (string) ($data['payment_method'] ?? 'manual'),
                    'reference' => $data['payment_reference'] ?? null,
                    'status' => 'paid',
                    'paid_at' => CarbonImmutable::now(),
                    'notes' => $data['payment_notes'] ?? 'Demo tenant converted to paid subscription',
                ]);
            }

            return [
                'tenant' => $tenant->refresh()->load(['plan', 'domains']),
                'subscription' => $subscription->refresh()->load(['plan', 'tenant']),
                'payment' => $payment?->refresh(),
            ];
        });

        return $result;
    }

    private function notifyExpired(Tenant $tenant): bool
    {
        $title = 'انتهى تفعيل حسابك التجريبي';
        $message = 'حسابك التجريبي انتهى. يرجى التواصل معنا لتجديد الاشتراك وتفعيل حسابك من جديد.';

        $ok = false;

        $this->platformMailService->trySend(function () use ($tenant, $title, $message): void {
            foreach ($this->platformMailService->resolveTenantEmails($tenant) as $email) {
                $this->platformMailService->sendDemoExpired(
                    $email,
                    (string) $tenant->name,
                    $title,
                    $message,
                );
            }
        });
        $ok = true;

        try {
            $this->tenantDatabaseManager->connect($tenant);
            $this->tenantNotifier->broadcast(
                $title,
                $message,
                'billing',
                'high',
                '/subscription',
            );
        } catch (Throwable $exception) {
            Log::warning('Demo expiry in-app notify failed', [
                'tenant_id' => $tenant->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return $ok;
    }

    private function primaryHostname(string $slug): string
    {
        $baseDomains = config('tenancy.domain.base_domains', ['dressnmore.it.com']);
        $baseDomain = is_array($baseDomains) && $baseDomains !== []
            ? (string) $baseDomains[0]
            : 'dressnmore.it.com';

        return strtolower($slug.'.'.$baseDomain);
    }
}
