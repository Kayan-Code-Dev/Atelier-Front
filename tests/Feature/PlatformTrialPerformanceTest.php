<?php

namespace Tests\Feature;

use App\Models\Central\PlatformPermission;
use App\Models\Central\PlatformRole;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Models\Central\TrialOnboardingEvent;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbox;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Dress;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Permission;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Role;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformTrialPerformanceTest extends TestCase
{
    private string $centralDatabasePath;

    private string $tenantDatabasePath;

    private string $otherTenantDatabasePath;

    private SuperAdmin $admin;

    private Tenant $demoTenant;

    private Tenant $otherDemoTenant;

    private Tenant $paidTenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSqliteDatabases();
        $this->runMigrations();
        $this->seedTenantPermissions();
        $this->admin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $this->demoTenant = $this->createTenant('demo-atelier', $this->tenantDatabasePath, ['source' => 'demo']);
        $this->paidTenant = $this->createTenant('paid-atelier', $this->tenantDatabasePath, []);
        $this->otherDemoTenant = $this->createTenant('demo-other', $this->otherTenantDatabasePath, ['source' => 'demo']);
        $this->owner = $this->createTenantUserWithPermissions($this->journeyPermissions());
    }

    public function test_unauthenticated_admin_cannot_open_performance(): void
    {
        $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance")
            ->assertUnauthorized();
    }

    public function test_limited_admin_cannot_open_performance(): void
    {
        $role = PlatformRole::query()->create([
            'name' => 'Viewer',
            'slug' => 'viewer-no-demo',
            'is_system' => false,
        ]);
        PlatformPermission::query()->create([
            'key' => 'view_dashboard',
            'name' => 'Dashboard',
            'module' => 'dashboard',
            'sort_order' => 1,
        ]);
        $limited = SuperAdmin::query()->create([
            'name' => 'Limited',
            'email' => 'limited@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'platform_role_id' => $role->id,
        ]);

        Sanctum::actingAs($limited, ['*']);
        $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance")
            ->assertForbidden();
    }

    public function test_paid_never_demo_tenant_is_not_found(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $this->getJson("/api/platform/demo-tenants/{$this->paidTenant->id}/performance")
            ->assertNotFound();
    }

    public function test_fresh_trial_is_cold_and_not_activated(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance");
        $response->assertOk()
            ->assertJsonPath('data.trial.status', 'active')
            ->assertJsonPath('data.health.score', 0)
            ->assertJsonPath('data.activation.status', 'not_activated')
            ->assertJsonPath('data.engagement.level', 'cold')
            ->assertJsonPath('data.onboarding.percent', 0)
            ->assertJsonPath('data.business_metrics.customers', 0)
            ->assertJsonPath('data.activity.items', [])
            ->assertJsonPath('data.sales_priority.level', 'low');
    }

    public function test_admin_performance_e2e_after_onboarding_journey(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $headers = $this->tenantHeaders($this->demoTenant);
        $this->postJson('/api/tenant/trial-onboarding/start', [], $headers)->assertOk();
        $this->seedOperatingCycle($headers);
        $this->postJson('/api/tenant/trial-onboarding/views', ['step' => 'balances_review'], $headers)->assertOk();
        $this->postJson('/api/tenant/trial-onboarding/views', ['step' => 'account_statement'], $headers)->assertOk();
        $this->postJson('/api/tenant/trial-onboarding/signals', ['signal' => 'upgrade_clicked'], $headers)->assertOk();

        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance");
        $response->assertOk()
            ->assertJsonPath('data.trial.id', $this->demoTenant->id)
            ->assertJsonPath('data.onboarding.percent', 100)
            ->assertJsonPath('data.activation.status', 'fully_activated')
            ->assertJsonPath('data.pricing.upgrade_clicked', true)
            ->assertJsonPath('data.sales_priority.level', 'high');

        $this->assertGreaterThanOrEqual(1, (int) $response->json('data.business_metrics.customers'));
        $this->assertGreaterThanOrEqual(1, (int) $response->json('data.business_metrics.reservations'));
        $this->assertGreaterThanOrEqual(
            1,
            (int) $response->json('data.business_metrics.products')
            + (int) $response->json('data.business_metrics.inventory_items'),
        );

        $this->assertGreaterThanOrEqual(80, (int) $response->json('data.health.score'));
        $this->assertNotEmpty($response->json('data.health.why'));
        $this->assertNotEmpty($response->json('data.activity.items'));
        foreach ($response->json('data.activity.items') as $item) {
            $this->assertStringNotContainsString('trial_', (string) $item['label']);
            $this->assertNotSame($item['key'], $item['label']);
        }
        $this->assertSame('hot', $response->json('data.engagement.level'));
        $this->assertContains('reservation_created', array_column($response->json('data.onboarding.steps'), 'key'));
    }

    public function test_metrics_come_from_domain_not_event_counts(): void
    {
        Customer::query()->create(['name' => 'نورة', 'phone' => '0111111111', 'status' => 'active']);
        Customer::query()->create(['name' => 'هند', 'phone' => '0122222222', 'status' => 'active']);
        TrialOnboardingEvent::query()->create([
            'tenant_id' => $this->demoTenant->id,
            'user_id' => $this->owner->id,
            'event_name' => 'trial_customer_created',
            'step_key' => 'customer_setup',
            'metadata' => ['source' => 'trial'],
            'occurred_at' => now(),
        ]);

        Sanctum::actingAs($this->admin, ['*']);
        $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance")
            ->assertOk()
            ->assertJsonPath('data.business_metrics.customers', 2);
    }

    public function test_trial_a_does_not_see_trial_b_events(): void
    {
        TrialOnboardingEvent::query()->create([
            'tenant_id' => $this->demoTenant->id,
            'user_id' => $this->owner->id,
            'event_name' => 'trial_reservation_created',
            'step_key' => 'reservation_created',
            'metadata' => ['source' => 'trial'],
            'occurred_at' => now(),
        ]);

        Sanctum::actingAs($this->admin, ['*']);
        $other = $this->getJson("/api/platform/demo-tenants/{$this->otherDemoTenant->id}/performance")
            ->assertOk();
        $this->assertSame(0, (int) $other->json('data.activity.meta.total'));
        $this->assertSame(0, (int) $other->json('data.business_metrics.customers'));

        $self = $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance")->assertOk();
        $this->assertGreaterThan(0, (int) $self->json('data.activity.meta.total'));
    }

    public function test_expired_and_converted_trials_keep_performance_history(): void
    {
        TrialOnboardingEvent::query()->create([
            'tenant_id' => $this->demoTenant->id,
            'user_id' => $this->owner->id,
            'event_name' => 'trial_branch_created',
            'step_key' => 'branch_setup',
            'metadata' => ['source' => 'trial'],
            'occurred_at' => now()->subDay(),
        ]);

        $this->demoTenant->update([
            'status' => 'expired',
            'subscription_ends_at' => CarbonImmutable::now()->subDay(),
        ]);

        Sanctum::actingAs($this->admin, ['*']);
        $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance")
            ->assertOk()
            ->assertJsonPath('data.trial.status', 'expired')
            ->assertJsonPath('data.trial.expired', true);

        $this->demoTenant->update([
            'status' => 'active',
            'metadata' => [
                'converted_from_demo' => true,
                'converted_at' => CarbonImmutable::now()->toIso8601String(),
            ],
        ]);

        $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance")
            ->assertOk()
            ->assertJsonPath('data.trial.status', 'converted')
            ->assertJsonPath('data.trial.converted', true)
            ->assertJsonPath('data.lifecycle.current', 'converted');
    }

    public function test_activity_pagination_and_filters(): void
    {
        for ($i = 0; $i < 25; $i++) {
            TrialOnboardingEvent::query()->create([
                'tenant_id' => $this->demoTenant->id,
                'user_id' => $this->owner->id,
                'event_name' => 'trial_event_'.$i,
                'step_key' => null,
                'metadata' => ['source' => 'trial'],
                'occurred_at' => now()->subMinutes($i),
            ]);
        }
        TrialOnboardingEvent::query()->create([
            'tenant_id' => $this->demoTenant->id,
            'user_id' => $this->owner->id,
            'event_name' => 'trial_customer_created',
            'step_key' => 'customer_setup',
            'metadata' => ['source' => 'trial'],
            'occurred_at' => now(),
        ]);

        Sanctum::actingAs($this->admin, ['*']);
        $page1 = $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance?per_page=10&page=1")
            ->assertOk();
        $this->assertCount(10, $page1->json('data.activity.items'));
        $this->assertSame(10, (int) $page1->json('data.activity.meta.per_page'));
        $this->assertGreaterThan(1, (int) $page1->json('data.activity.meta.last_page'));

        $filtered = $this->getJson("/api/platform/demo-tenants/{$this->demoTenant->id}/performance?category=customers")
            ->assertOk();
        foreach ($filtered->json('data.activity.items') as $item) {
            $this->assertSame('customers', $item['category']);
        }
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function seedOperatingCycle(array $headers): void
    {
        $this->postJson('/api/tenant/branches', [
            'branch_code' => 'BR-P01',
            'name' => 'الفرع الرئيسي',
            'status' => 'active',
        ], $headers)->assertCreated();

        $branchId = (int) Branch::query()->value('id');
        $this->postJson('/api/tenant/cashboxes', [
            'name' => 'خزنة الفرع',
            'branch_id' => $branchId,
            'initial_balance' => 0,
            'is_active' => true,
        ], $headers)->assertCreated();

        $this->postJson('/api/tenant/suppliers', [
            'name' => 'مورد الأقمشة',
            'status' => 'active',
        ], $headers)->assertCreated();

        $supplierId = (int) Supplier::query()->value('id');
        $po = $this->postJson('/api/tenant/purchase-orders', [
            'supplier_id' => $supplierId,
            'branch_id' => $branchId,
            'status' => PurchaseOrder::STATUS_CONFIRMED,
            'order_date' => '2026-08-13',
            'items' => [
                ['item_name' => 'فستان سهرة', 'quantity' => 2, 'unit_price' => 100],
            ],
        ], $headers);
        $po->assertCreated();
        $poId = (int) $po->json('data.id');

        $receive = $this->postJson("/api/tenant/purchase-orders/{$poId}/receive", [], $headers);
        if ($receive->status() >= 400) {
            PurchaseOrder::query()->whereKey($poId)->update(['received_at' => now()]);
            Dress::query()->create([
                'name' => 'فستان سهرة',
                'code' => 'PO-P-1',
                'branch_id' => $branchId,
                'entity_type' => 'purchase_order',
                'entity_id' => $poId,
                'status' => Dress::STATUS_AVAILABLE,
            ]);
        }

        $this->postJson('/api/tenant/customers', [
            'name' => 'سارة',
            'phone' => '0100000000',
            'status' => 'active',
        ], $headers)->assertCreated();

        Invoice::query()->create([
            'invoice_number' => 'RENT-P-1',
            'customer_id' => (int) Customer::query()->value('id'),
            'branch_id' => $branchId,
            'type' => Invoice::TYPE_RENT,
            'status' => Invoice::STATUS_CONFIRMED,
            'total' => 200,
            'remaining_amount' => 200,
        ]);
    }

    /**
     * @return list<string>
     */
    private function journeyPermissions(): array
    {
        return [
            'branches.view', 'branches.create',
            'cashboxes.view', 'cashboxes.create',
            'suppliers.view', 'suppliers.create',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.receive',
            'customers.view', 'customers.create',
            'invoices.view', 'invoices.create',
        ];
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }
        $this->centralDatabasePath = $testingPath.'/central-trial-performance.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-trial-performance.sqlite';
        $this->otherTenantDatabasePath = $testingPath.'/tenant-trial-performance-b.sqlite';
        foreach ([$this->centralDatabasePath, $this->tenantDatabasePath, $this->otherTenantDatabasePath] as $path) {
            @unlink($path);
            touch($path);
        }

        Config::set('database.default', 'central');
        Config::set('database.connections.central', [
            'driver' => 'sqlite', 'database' => $this->centralDatabasePath, 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        Config::set('database.connections.tenant', [
            'driver' => 'sqlite', 'database' => $this->tenantDatabasePath, 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        DB::purge('central');
        DB::purge('tenant');
    }

    private function runMigrations(): void
    {
        Artisan::call('migrate:fresh', ['--database' => 'central', '--force' => true]);
        $this->migrateTenantDatabase($this->tenantDatabasePath);
        $this->migrateTenantDatabase($this->otherTenantDatabasePath);
        Config::set('database.connections.tenant.database', $this->tenantDatabasePath);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    private function migrateTenantDatabase(string $path): void
    {
        Config::set('database.connections.tenant.database', $path);
        DB::purge('tenant');
        DB::reconnect('tenant');
        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => base_path('database/migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    private function seedTenantPermissions(): void
    {
        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => TenantRolePermissionSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function createTenant(string $slug, string $databaseName, array $metadata): Tenant
    {
        return Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'database_name' => $databaseName,
            'status' => 'active',
            'metadata' => $metadata === [] ? null : $metadata,
            'subscription_starts_at' => CarbonImmutable::now()->subDays(4),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(3),
        ]);
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function createTenantUserWithPermissions(array $permissionKeys): User
    {
        $role = Role::query()->create(['name' => 'Role '.uniqid(), 'slug' => 'role-'.uniqid()]);
        $permissionIds = Permission::query()->whereIn('key', $permissionKeys)->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        $user = User::query()->create([
            'name' => 'Tenant User '.uniqid(),
            'email' => uniqid().'@tenant.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function tenantHeaders(Tenant $tenant): array
    {
        return ['Accept' => 'application/json', 'X-Tenant' => $tenant->slug];
    }
}
