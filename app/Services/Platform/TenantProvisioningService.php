<?php

namespace App\Services\Platform;

use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use App\Models\Central\TenantDomain;
use App\Models\Central\TenantProvisioningLog;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Services\Tenant\TenantDatabaseManager;
use App\Services\Tenant\TenantUserDirectoryService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TenantProvisioningService
{
    public function __construct(
        private readonly TenantDatabaseManager $tenantDatabaseManager,
        private readonly TenantUserDirectoryService $tenantUserDirectoryService,
    ) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Tenant::query()
            ->with(['plan', 'domains', 'customSubscription'])
            ->latest('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $wildcard = '%'.mb_strtolower($search).'%';
            $query->where(function (Builder $builder) use ($wildcard): void {
                $builder->whereRaw('LOWER(name) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(slug) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(database_name) LIKE ?', [$wildcard]);
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $planId = $filters['plan_id'] ?? null;
        if ($planId !== null && trim((string) $planId) !== '') {
            $query->where('plan_id', (int) $planId);
        }

        $source = trim((string) ($filters['source'] ?? ''));
        if ($source !== '') {
            $query->where('metadata->source', $source);
        } elseif (! array_key_exists('include_demo', $filters) || ! $filters['include_demo']) {
            // Regular ateliers list: hide demo tenants (they have their own module).
            $query->where(function (Builder $builder): void {
                $builder->whereNull('metadata->source')
                    ->orWhere('metadata->source', '!=', 'demo');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Tenant
    {
        $name = trim((string) $data['name']);
        $slug = $this->resolveSlug($data['slug'] ?? $name);
        $databaseName = $this->resolveDatabaseName(
            provided: $data['database_name'] ?? null,
            slug: $slug,
        );

        $customService = app(CustomTenantSubscriptionService::class);
        $isCustom = $customService->isCustomPayload($data);

        $planId = $isCustom
            ? null
            : (array_key_exists('plan_id', $data) && $data['plan_id'] !== null && $data['plan_id'] !== ''
                ? (int) $data['plan_id']
                : null);

        $customPayload = is_array($data['custom_subscription'] ?? null) ? $data['custom_subscription'] : [];
        $startsAt = $data['subscription_starts_at']
            ?? $customPayload['starts_at']
            ?? CarbonImmutable::now();
        $endsAt = $data['subscription_ends_at'] ?? $customPayload['ends_at'] ?? null;
        if ($endsAt === null) {
            if ($isCustom) {
                $interval = (string) ($customPayload['billing_interval'] ?? 'monthly');
                $start = $startsAt instanceof CarbonImmutable
                    ? $startsAt
                    : CarbonImmutable::parse((string) $startsAt);
                $endsAt = $customService->endsAtFrom($start, $interval);
            } else {
                $endsAt = $planId !== null
                    ? CarbonImmutable::now()->addDays($this->resolveDurationDays($planId))
                    : CarbonImmutable::now()->addDays(7);
            }
        }

        $tenant = Tenant::query()->create([
            'name' => $name,
            'slug' => $slug,
            'database_name' => $databaseName,
            'status' => 'provisioning',
            'plan_id' => $planId,
            'subscription_starts_at' => $startsAt,
            'subscription_ends_at' => $endsAt,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->log($tenant, 'tenant_created', 'success', 'Tenant record created');

        $tenant = $tenant->refresh()->load(['plan', 'domains', 'customSubscription']);
        if ($isCustom) {
            $customService->assign($tenant, array_merge($customPayload, [
                'starts_at' => $customPayload['starts_at'] ?? $startsAt,
                'ends_at' => $customPayload['ends_at'] ?? $endsAt,
            ]));
        } else {
            app(SubscriptionAdminService::class)->ensureForTenant($tenant);
        }

        return $tenant->refresh()->load(['plan', 'domains', 'customSubscription']);
    }

    public function provision(array $data): Tenant
    {
        $tenant = $this->create($data);

        return $this->migrate($tenant);
    }

    public function migrate(Tenant $tenant): Tenant
    {
        $databaseName = (string) $tenant->database_name;

        try {
            if (! $this->tenantDatabaseManager->databaseExists($databaseName)) {
                $this->tenantDatabaseManager->createDatabase($databaseName);
                $this->log($tenant, 'database_created', 'success', 'Tenant database created', [
                    'database_name' => $databaseName,
                ]);
            } else {
                $this->log($tenant, 'database_exists', 'success', 'Tenant database already exists', [
                    'database_name' => $databaseName,
                ]);
            }

            $this->tenantDatabaseManager->connect($tenant);
            $this->tenantDatabaseManager->runTenantMigrations();
            $this->tenantDatabaseManager->runTenantSeeders();
            $this->tenantDatabaseManager->testConnection();

            $tenant->status = 'active';
            $tenant->save();

            $this->log($tenant, 'migration_completed', 'success', 'Tenant migration completed');

            return $tenant->refresh()->load(['plan', 'domains']);
        } catch (Throwable $exception) {
            $tenant->status = 'provisioning_failed';
            $tenant->save();

            $this->log($tenant, 'migration_failed', 'failed', $exception->getMessage(), [
                'database_name' => $databaseName,
            ]);

            throw new RuntimeException('Tenant migration failed.', 0, $exception);
        }
    }

    /**
     * @return array{email: string, password: string, username: ?string, admin: array{email: string, password: string, username: ?string}}
     */
    public function seedAdmin(Tenant $tenant, array $data): array
    {
        $email = strtolower(trim((string) ($data['admin_email'] ?? $data['email'] ?? '')));
        if ($email === '') {
            $email = 'admin@'.$tenant->slug.'.local';
        }

        $password = trim((string) ($data['admin_password'] ?? $data['password'] ?? ''));
        if ($password === '') {
            $password = Str::random(16);
        }

        $adminName = trim((string) ($data['admin_name'] ?? $data['username'] ?? $data['admin_username'] ?? ''));
        if ($adminName === '') {
            $adminName = $tenant->name.' Admin';
        }

        $phone = trim((string) ($data['phone'] ?? ''));

        $this->tenantDatabaseManager->connect($tenant);

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existingUser instanceof User) {
            $existingUser->forceFill([
                'name' => $adminName,
                'password' => Hash::make($password),
                'phone' => $phone !== '' ? $phone : $existingUser->phone,
                'status' => 'active',
            ])->save();

            $this->log($tenant, 'admin_password_reset', 'success', 'Tenant admin password reset');
        } else {
            $existingUser = User::query()->create([
                'name' => $adminName,
                'email' => $email,
                'password' => $password,
                'phone' => $phone !== '' ? $phone : null,
                'status' => 'active',
            ]);

            $this->log($tenant, 'admin_user_created', 'success', 'Tenant admin user created');
        }

        $ownerRole = Role::query()->where('slug', 'owner')->first();
        if ($ownerRole) {
            $existingUser->roles()->syncWithoutDetaching([$ownerRole->id]);
        }

        $metadata = is_array($tenant->metadata) ? $tenant->metadata : [];
        $metadata['admin_email'] = $email;
        $metadata['admin_name'] = $adminName;
        if ($phone !== '') {
            $metadata['phone'] = $phone;
        }

        $tenant->metadata = $metadata;
        $tenant->save();

        $this->tenantUserDirectoryService->register($tenant, $email);

        $username = trim((string) ($data['admin_username'] ?? $data['username'] ?? ''));

        return [
            'email' => $email,
            'password' => $password,
            'username' => $username !== '' ? $username : null,
            'admin' => [
                'email' => $email,
                'password' => $password,
                'username' => $username !== '' ? $username : null,
            ],
        ];
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->name = trim((string) $data['name']);

        $customService = app(CustomTenantSubscriptionService::class);
        $wantsCustom = $customService->isCustomPayload($data);

        if ($wantsCustom) {
            $tenant->save();
            $customService->assign($tenant, is_array($data['custom_subscription'] ?? null) ? $data['custom_subscription'] : []);

            return $tenant->refresh()->load(['plan', 'domains', 'customSubscription']);
        }

        if (array_key_exists('plan_id', $data) && $data['plan_id'] !== null && $data['plan_id'] !== '') {
            if ($tenant->isOnCustomPlan()) {
                $customService->clear($tenant);
            }
            $tenant->plan_id = (int) $data['plan_id'];
        }

        if (array_key_exists('subscription_starts_at', $data)) {
            $tenant->subscription_starts_at = $data['subscription_starts_at'];
        }

        if (array_key_exists('subscription_ends_at', $data)) {
            $tenant->subscription_ends_at = $data['subscription_ends_at'];
        }

        $tenant->save();

        $tenant = $tenant->refresh()->load(['plan', 'domains', 'customSubscription']);
        $subscriptionService = app(SubscriptionAdminService::class);
        $subscription = $subscriptionService->ensureForTenant($tenant);
        if ($subscription !== null && ! $subscription->is_custom) {
            $dirty = false;
            if (array_key_exists('plan_id', $data) && $data['plan_id'] !== null) {
                $subscription->plan_id = (int) $data['plan_id'];
                $dirty = true;
            }
            if (array_key_exists('subscription_starts_at', $data) && $data['subscription_starts_at'] !== null) {
                $subscription->starts_at = $data['subscription_starts_at'];
                $dirty = true;
            }
            if (array_key_exists('subscription_ends_at', $data) && $data['subscription_ends_at'] !== null) {
                $subscription->ends_at = $data['subscription_ends_at'];
                $dirty = true;
            }
            if ($dirty) {
                if ($subscription->ends_at !== null
                    && CarbonImmutable::parse((string) $subscription->ends_at)->lt(CarbonImmutable::now())
                ) {
                    $subscription->status = 'expired';
                } elseif ($subscription->status === 'expired') {
                    $subscription->status = 'active';
                }
                $subscription->save();
            }
        }

        return $tenant->refresh()->load(['plan', 'domains', 'customSubscription']);
    }

    public function destroy(Tenant $tenant): void
    {
        $databaseName = (string) $tenant->database_name;

        $tenant->domains()->delete();
        $this->tenantUserDirectoryService->removeForTenant($tenant);
        $tenant->provisioningLogs()->delete();
        $tenant->delete();

        if ($databaseName !== '' && $this->tenantDatabaseManager->databaseExists($databaseName)) {
            $this->tenantDatabaseManager->dropDatabase($databaseName);
        }
    }

    public function addDomain(Tenant $tenant, string $domain): TenantDomain
    {
        $normalizedDomain = strtolower(trim($domain));

        return TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'domain' => $normalizedDomain,
            'is_primary' => $tenant->domains()->count() === 0,
            'status' => 'active',
        ]);
    }

    public function deleteDomain(Tenant $tenant, TenantDomain $domain): void
    {
        if ((int) $domain->tenant_id !== (int) $tenant->id) {
            throw new RuntimeException('Domain does not belong to tenant.');
        }

        $domain->delete();
    }

    public function suspend(Tenant $tenant): Tenant
    {
        $tenant->status = 'suspended';
        $tenant->save();

        return $tenant->refresh()->load(['plan', 'domains']);
    }

    public function activate(Tenant $tenant): Tenant
    {
        $tenant->status = 'active';
        $tenant->save();

        return $tenant->refresh()->load(['plan', 'domains']);
    }

    public function renew(Tenant $tenant, array $data): Tenant
    {
        $custom = $tenant->currentCustomSubscription();
        $daysProvided = array_key_exists('days', $data) && $data['days'] !== null && $data['days'] !== '';

        if (isset($data['subscription_ends_at'])) {
            $subscriptionEndsAt = CarbonImmutable::parse((string) $data['subscription_ends_at']);
        } else {
            $base = $tenant->subscription_ends_at !== null
                ? CarbonImmutable::parse((string) $tenant->subscription_ends_at)
                : CarbonImmutable::now();

            if ($base->lt(CarbonImmutable::now())) {
                $base = CarbonImmutable::now();
            }

            if ($custom instanceof \App\Models\Central\Subscription && ! $daysProvided) {
                $subscriptionEndsAt = app(CustomTenantSubscriptionService::class)
                    ->extendByInterval($custom, $base);
            } else {
                $days = (int) ($data['days'] ?? 30);
                $subscriptionEndsAt = $base->addDays($days);
            }
        }

        if ($tenant->subscription_starts_at === null) {
            $tenant->subscription_starts_at = CarbonImmutable::now();
        }

        $tenant->subscription_ends_at = $subscriptionEndsAt;
        $tenant->status = 'active';
        $tenant->save();

        $this->log($tenant, 'subscription_renewed', 'success', 'Tenant subscription renewed', [
            'subscription_ends_at' => $subscriptionEndsAt->toISOString(),
        ]);

        $tenant = $tenant->refresh()->load(['plan', 'domains']);
        $subscriptionService = app(SubscriptionAdminService::class);
        $subscription = $subscriptionService->ensureForTenant($tenant);
        if ($subscription !== null) {
            $subscription->ends_at = $subscriptionEndsAt;
            $subscription->status = 'active';
            if ($subscription->starts_at === null) {
                $subscription->starts_at = CarbonImmutable::now();
            }
            $subscription->save();
        }

        return $tenant->refresh()->load(['plan', 'domains']);
    }

    private function resolveDurationDays(int $planId): int
    {
        $plan = Plan::query()->find($planId);

        return max(1, (int) ($plan?->duration_days ?? 365));
    }

    private function resolveSlug(mixed $slugInput): string
    {
        $baseSlug = Str::slug(trim((string) $slugInput));
        if ($baseSlug === '') {
            $baseSlug = 'tenant';
        }

        $candidate = $baseSlug;
        $counter = 2;

        while (Tenant::query()->where('slug', $candidate)->exists()) {
            $candidate = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $candidate;
    }

    private function resolveDatabaseName(mixed $provided, string $slug): string
    {
        $driver = (string) Config::get('database.connections.tenant.driver', 'mysql');
        $providedName = trim((string) $provided);

        if ($driver === 'sqlite') {
            if ($providedName !== '') {
                return $this->sqliteDatabasePath($providedName);
            }

            $filename = $slug.'.sqlite';

            return storage_path('framework/tenants/'.$filename);
        }

        if ($providedName !== '') {
            return $providedName;
        }

        $prefix = (string) config('tenancy.provisioning.database_prefix', 'tenant_');
        $suffix = (string) config('tenancy.provisioning.database_suffix', '');
        $base = preg_replace('/[^A-Za-z0-9_]/', '_', $prefix.$slug.$suffix) ?: 'tenant_db';

        $candidate = $base;
        $counter = 2;

        while (Tenant::query()->where('database_name', $candidate)->exists()) {
            $candidate = "{$base}_{$counter}";
            $counter++;
        }

        return $candidate;
    }

    private function sqliteDatabasePath(string $providedName): string
    {
        if ($providedName === ':memory:') {
            return $providedName;
        }

        if (str_starts_with($providedName, '/')) {
            return $providedName;
        }

        $filename = str_ends_with($providedName, '.sqlite') ? $providedName : $providedName.'.sqlite';

        return storage_path('framework/tenants/'.$filename);
    }

    private function log(
        ?Tenant $tenant,
        string $step,
        string $status,
        ?string $message = null,
        array $context = []
    ): void {
        TenantProvisioningLog::query()->create([
            'tenant_id' => $tenant?->id,
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'context' => $context === [] ? null : $context,
        ]);
    }
}
