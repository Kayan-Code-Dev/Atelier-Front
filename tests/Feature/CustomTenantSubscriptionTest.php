<?php

namespace Tests\Feature;

use App\Models\Central\Plan;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Support\PlanFeatureGate;
use App\Support\TenantSubscriptionPresenter;
use Carbon\CarbonImmutable;
use Database\Seeders\Central\PlanSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomTenantSubscriptionTest extends TestCase
{
    private string $centralDatabasePath;

    private SuperAdmin $admin;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }

        $this->centralDatabasePath = $testingPath.'/central-custom-subscription.sqlite';
        @unlink($this->centralDatabasePath);
        touch($this->centralDatabasePath);

        Config::set('database.default', 'central');
        Config::set('database.connections.central', [
            'driver' => 'sqlite',
            'database' => $this->centralDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('central');

        Artisan::call('migrate:fresh', [
            '--database' => 'central',
            '--force' => true,
        ]);
        Artisan::call('db:seed', [
            '--database' => 'central',
            '--class' => PlanSeeder::class,
            '--force' => true,
        ]);

        $this->plan = Plan::query()->where('slug', 'basic')->firstOrFail();
        $this->admin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform-custom@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_tenant_with_custom_plan_without_catalog_row(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $plansBefore = Plan::query()->count();

        $response = $this->postJson('/api/platform/tenants', [
            'name' => 'Custom Atelier',
            'slug' => 'custom-atelier',
            'database_name' => storage_path('framework/testing/custom-atelier.sqlite'),
            'custom_plan' => true,
            'custom_subscription' => [
                'monthly_price' => 250,
                'yearly_price' => 2400,
                'billing_interval' => 'monthly',
                'starts_at' => '2026-08-18',
                'currency' => 'EGP',
                'features' => [
                    'customers.enabled' => true,
                    'categories.enabled' => true,
                    'branches.max' => 2,
                    'users.max' => 5,
                ],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_custom_plan', true)
            ->assertJsonPath('data.plan.slug', 'custom')
            ->assertJsonPath('data.plan_id', null)
            ->assertJsonPath('data.custom_subscription.billing_interval', 'monthly')
            ->assertJsonPath('data.custom_subscription.monthly_price', '250.00');

        $this->assertSame($plansBefore, Plan::query()->count());

        $tenantId = (int) $response->json('data.id');
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenantId,
            'is_custom' => 1,
            'plan_id' => null,
            'billing_interval' => 'monthly',
        ], 'central');

        $this->assertNull(Tenant::query()->find($tenantId)?->plan_id);
    }

    public function test_custom_subscription_is_feature_gating_source_of_truth(): void
    {
        $tenant = $this->createCustomTenant([
            'customers.enabled' => 'true',
            'categories.enabled' => 'false',
            'branches.max' => '3',
        ]);

        $gate = app(PlanFeatureGate::class);

        $this->assertTrue($gate->isEnabled($tenant, 'customers.enabled'));
        $this->assertFalse($gate->isEnabled($tenant, 'categories.enabled'));
        $this->assertSame(3, $gate->limit($tenant, 'branches.max'));
        $this->assertFalse($gate->isEnabled($tenant, 'factory.enabled'));
    }

    public function test_presenter_shows_custom_plan_not_catalog_plan(): void
    {
        $tenant = $this->createCustomTenant([
            'customers.enabled' => 'true',
        ]);

        $payload = app(TenantSubscriptionPresenter::class)->forTenant($tenant->refresh());

        $this->assertTrue($payload['is_custom']);
        $this->assertSame('custom', $payload['plan_code']);
        $this->assertSame('Custom Plan', $payload['plan_name']);
        $this->assertSame(250.0, $payload['price']);
        $this->assertSame('monthly', $payload['billing_cycle']);
        $this->assertFalse($payload['can_renew']);
        $this->assertNull($payload['plan_id']);
    }

    public function test_renewal_keeps_custom_price_and_interval(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $tenant = $this->createCustomTenant(['customers.enabled' => 'true']);
        $subscription = $tenant->currentCustomSubscription();
        $this->assertNotNull($subscription);

        $originalMonthly = (string) $subscription->price_monthly;
        $originalYearly = (string) $subscription->price_yearly;
        $originalInterval = $subscription->billing_interval;
        $oldEnd = CarbonImmutable::parse((string) $subscription->ends_at);

        $this->postJson("/api/platform/tenants/{$tenant->id}/renew", [], [
            'Accept' => 'application/json',
        ])->assertOk();

        $subscription->refresh();
        $this->assertSame($originalMonthly, (string) $subscription->price_monthly);
        $this->assertSame($originalYearly, (string) $subscription->price_yearly);
        $this->assertSame($originalInterval, $subscription->billing_interval);
        $this->assertTrue($subscription->is_custom);
        $this->assertNull($subscription->plan_id);
        $this->assertTrue(
            CarbonImmutable::parse((string) $subscription->ends_at)->greaterThan($oldEnd)
        );
    }

    public function test_catalog_tenant_still_uses_plan_features(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Catalog Tenant',
            'slug' => 'catalog-tenant',
            'plan_id' => $this->plan->id,
            'database_name' => storage_path('framework/testing/catalog-tenant.sqlite'),
            'status' => 'active',
            'subscription_starts_at' => CarbonImmutable::now(),
            'subscription_ends_at' => CarbonImmutable::now()->addMonth(),
        ]);

        $payload = app(TenantSubscriptionPresenter::class)->forTenant($tenant);

        $this->assertFalse($payload['is_custom']);
        $this->assertNotSame('custom', $payload['plan_code']);
        $this->assertSame($this->plan->id, $payload['plan_id']);
    }

    /**
     * @param  array<string, string>  $features
     */
    private function createCustomTenant(array $features): Tenant
    {
        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->postJson('/api/platform/tenants', [
            'name' => 'Gated Custom',
            'slug' => 'gated-custom-'.uniqid(),
            'database_name' => storage_path('framework/testing/gated-custom-'.uniqid().'.sqlite'),
            'custom_plan' => true,
            'custom_subscription' => [
                'monthly_price' => 250,
                'yearly_price' => 2400,
                'billing_interval' => 'monthly',
                'starts_at' => CarbonImmutable::now()->toDateString(),
                'currency' => 'EGP',
                'features' => $features,
            ],
        ], ['Accept' => 'application/json']);

        $response->assertCreated();

        return Tenant::query()->findOrFail((int) $response->json('data.id'));
    }
}
