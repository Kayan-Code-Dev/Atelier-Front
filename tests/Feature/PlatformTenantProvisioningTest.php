<?php

namespace Tests\Feature;

use App\Models\Central\Plan;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use Database\Seeders\Central\PlanSeeder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformTenantProvisioningTest extends TestCase
{
    private string $centralDatabasePath;

    private string $tenantTemplateDatabasePath;

    private SuperAdmin $admin;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSqliteDatabases();
        $this->runCentralMigrations();
        $this->seedPlans();
        $this->admin = $this->createSuperAdmin();
    }

    public function test_platform_admin_can_create_tenant_via_api(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $tenantDatabasePath = storage_path('framework/testing/provisioned-tenant-api.sqlite');
        @unlink($tenantDatabasePath);

        $response = $this->postJson('/api/platform/tenants', [
            'name' => 'Atelier Cairo',
            'slug' => 'atelier-cairo',
            'plan_id' => $this->plan->id,
            'database_name' => $tenantDatabasePath,
            'subscription_starts_at' => '2026-05-26 00:00:00',
            'subscription_ends_at' => '2026-06-26 00:00:00',
            'metadata' => [
                'source' => 'api',
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Tenant created')
            ->assertJsonPath('data.slug', 'atelier-cairo')
            ->assertJsonPath('data.status', 'provisioning');

        $tenantId = (int) $response->json('data.id');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
            'status' => 'provisioning',
            'database_name' => $tenantDatabasePath,
        ], 'central');
        $this->assertDatabaseHas('tenant_provisioning_logs', [
            'tenant_id' => $tenantId,
            'step' => 'tenant_created',
            'status' => 'success',
        ], 'central');

        $migrateResponse = $this->postJson("/api/platform/tenants/{$tenantId}/migrate", [], [
            'Accept' => 'application/json',
        ]);

        $migrateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
            'status' => 'active',
        ], 'central');
        $this->assertDatabaseHas('tenant_provisioning_logs', [
            'tenant_id' => $tenantId,
            'step' => 'migration_completed',
            'status' => 'success',
        ], 'central');

        $this->assertFileExists($tenantDatabasePath);
    }

    public function test_platform_admin_can_list_tenants(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        Tenant::query()->create([
            'name' => 'Tenant One',
            'slug' => 'tenant-one',
            'plan_id' => $this->plan->id,
            'database_name' => storage_path('framework/testing/tenant-one.sqlite'),
            'status' => 'active',
            'subscription_starts_at' => CarbonImmutable::now()->subDay(),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(10),
        ]);

        $response = $this->getJson('/api/platform/tenants?search=tenant&status=active', [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'tenant-one');
    }

    public function test_platform_admin_can_suspend_tenant(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $tenant = $this->createTenant('active');

        $response = $this->postJson("/api/platform/tenants/{$tenant->id}/suspend", [], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');
    }

    public function test_platform_admin_can_activate_tenant(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $tenant = $this->createTenant('suspended');

        $response = $this->postJson("/api/platform/tenants/{$tenant->id}/activate", [], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_platform_admin_can_renew_tenant(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $tenant = $this->createTenant('expired');

        $response = $this->postJson("/api/platform/tenants/{$tenant->id}/renew", [
            'days' => 45,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $tenant->refresh();
        $this->assertNotNull($tenant->subscription_ends_at);
    }

    public function test_platform_admin_can_promote_demo_tenant_and_it_appears_in_tenants_list(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $demoTenant = Tenant::query()->create([
            'name' => 'Demo Atelier',
            'slug' => 'demo-atelier-'.uniqid(),
            'plan_id' => null,
            'database_name' => storage_path('framework/testing/demo-atelier-'.uniqid().'.sqlite'),
            'status' => 'active',
            'subscription_starts_at' => CarbonImmutable::now()->subDays(2),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(3),
            'metadata' => [
                'source' => 'demo',
                'demo_days' => 7,
                'admin_email' => 'demo@example.com',
            ],
        ]);

        $promoteResponse = $this->postJson("/api/platform/demo-tenants/{$demoTenant->id}/promote", [
            'plan_id' => $this->plan->id,
            'mark_as_paid' => true,
            'payment_method' => 'manual',
            'payment_reference' => 'INV-DEMO-001',
        ], [
            'Accept' => 'application/json',
        ]);

        $promoteResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tenant.id', $demoTenant->id)
            ->assertJsonPath('data.tenant.is_demo', false)
            ->assertJsonPath('data.subscription.status', 'active');

        $demoTenant->refresh();
        $this->assertSame($this->plan->id, (int) $demoTenant->plan_id);
        $this->assertTrue((bool) ($demoTenant->metadata['converted_from_demo'] ?? false));
        $this->assertArrayNotHasKey('source', (array) $demoTenant->metadata);

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $demoTenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ], 'central');

        $this->assertDatabaseHas('payments', [
            'tenant_id' => $demoTenant->id,
            'plan_id' => $this->plan->id,
            'purpose' => 'demo_conversion',
            'status' => 'paid',
        ], 'central');

        $tenantsResponse = $this->getJson('/api/platform/tenants?search=demo-atelier', [
            'Accept' => 'application/json',
        ]);
        $tenantsResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $demoTenant->id);

        $demoListResponse = $this->getJson('/api/platform/demo-tenants?search=demo-atelier', [
            'Accept' => 'application/json',
        ]);
        $demoListResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 0);
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }

        $this->centralDatabasePath = $testingPath.'/central-platform-tenants.sqlite';
        $this->tenantTemplateDatabasePath = $testingPath.'/tenant-template-platform-tenants.sqlite';

        @unlink($this->centralDatabasePath);
        @unlink($this->tenantTemplateDatabasePath);

        touch($this->centralDatabasePath);
        touch($this->tenantTemplateDatabasePath);

        Config::set('database.default', 'central');
        Config::set('database.connections.central', [
            'driver' => 'sqlite',
            'database' => $this->centralDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => $this->tenantTemplateDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('central');
        DB::purge('tenant');
    }

    private function runCentralMigrations(): void
    {
        Artisan::call('migrate:fresh', [
            '--database' => 'central',
            '--force' => true,
        ]);
    }

    private function seedPlans(): void
    {
        Artisan::call('db:seed', [
            '--database' => 'central',
            '--class' => PlanSeeder::class,
            '--force' => true,
        ]);

        $this->plan = Plan::query()->where('slug', 'basic')->firstOrFail();
    }

    private function createSuperAdmin(): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
    }

    private function createTenant(string $status): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Managed Tenant',
            'slug' => 'managed-'.uniqid(),
            'plan_id' => $this->plan->id,
            'database_name' => storage_path('framework/testing/managed-'.uniqid().'.sqlite'),
            'status' => $status,
            'subscription_starts_at' => CarbonImmutable::now()->subMonth(),
            'subscription_ends_at' => CarbonImmutable::now()->subDay(),
        ]);
    }
}
