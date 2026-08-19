<?php

namespace Tests\Feature;

use App\Models\Central\Plan;
use App\Models\Central\PlanFeature;
use App\Models\Central\Tenant;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Support\PlanFeatureCatalog;
use Carbon\CarbonImmutable;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlanEntitlementPlgTest extends TestCase
{
    private string $centralDatabasePath;

    private string $tenantDatabasePath;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSqliteDatabases();
        $this->runMigrations();
        $this->seedTenantPermissions();
        $this->tenant = $this->createTenantWithPlanSlug('free');
        $this->user = $this->createTenantUserWithPermissions([
            'customers.view',
            'website.view',
            'intelligence.view',
            'suppliers.view',
        ]);
    }

    public function test_free_plan_allows_customers(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $this->getJson('/api/tenant/customers', $this->tenantHeaders())
            ->assertOk();
    }

    public function test_free_plan_blocks_website_with_upgrade_payload(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/tenant/website/overview', $this->tenantHeaders());

        $response->assertForbidden()
            ->assertJsonPath('errors.code', 'plan_feature_required')
            ->assertJsonPath('errors.required_plan', 'starter')
            ->assertJsonPath('errors.recommended_plan', 'starter');
    }

    public function test_free_plan_blocks_ai_consultant_recommending_professional(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/tenant/intelligence/health', $this->tenantHeaders());

        $response->assertForbidden()
            ->assertJsonPath('errors.required_plan', 'professional');
    }

    public function test_me_lists_locked_modules_for_free_plan(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/tenant/me', $this->tenantHeaders());

        $response->assertOk()
            ->assertJsonPath('data.subscription.plan_code', 'free')
            ->assertJsonFragment(['customers']);

        $locked = $response->json('data.subscription.locked_modules') ?? [];
        $lockedKeys = collect($locked)->pluck('feature_key')->all();
        $this->assertContains('website.enabled', $lockedKeys);
        $this->assertContains('ai_assistant.enabled', $lockedKeys);
    }

    public function test_upgrade_recommendations_are_lowest_unlocking_plan(): void
    {
        $service = app(\App\Services\Platform\PlanEntitlementService::class);

        $this->assertSame('starter', $service->getUpgradeRecommendation($this->tenant, 'website.enabled'));
        $this->assertSame('professional', $service->getUpgradeRecommendation($this->tenant, 'ai_assistant.enabled'));
        $this->assertSame('business', $service->getUpgradeRecommendation($this->tenant, 'factory.enabled'));
    }

    public function test_starter_unlocks_website_not_ai(): void
    {
        $this->tenant = $this->createTenantWithPlanSlug('starter', 'starter-tenant');
        $this->user = $this->createTenantUserWithPermissions(['website.view', 'intelligence.view', 'customers.view']);

        Sanctum::actingAs($this->user, ['*']);

        $me = $this->getJson('/api/tenant/me', $this->tenantHeaders())->assertOk();
        $enabled = $me->json('data.subscription.enabled_modules') ?? [];
        $this->assertContains('website', $enabled);
        $this->assertNotContains('ai_assistant', $enabled);
    }

    public function test_comparison_matrix_includes_four_plans(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/tenant/subscription/feature-catalog', $this->tenantHeaders());
        $response->assertOk()
            ->assertJsonPath('data.plans.0', 'free')
            ->assertJsonPath('data.plans.3', 'business');
    }

    private function prepareSqliteDatabases(): void
    {
        $this->centralDatabasePath = database_path('testing-central-plg.sqlite');
        $this->tenantDatabasePath = database_path('testing-tenant-plg.sqlite');

        foreach ([$this->centralDatabasePath, $this->tenantDatabasePath] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
            touch($path);
        }

        Config::set('database.connections.central', [
            'driver' => 'sqlite',
            'database' => $this->centralDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => $this->tenantDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('database.default', 'central');
        DB::purge('central');
        DB::purge('tenant');
    }

    private function runMigrations(): void
    {
        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => database_path('migrations'),
            '--realpath' => true,
            '--force' => true,
        ]);
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => database_path('migrations/tenant'),
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

    private function createTenantWithPlanSlug(string $slug, string $tenantSlug = 'plg-tenant'): Tenant
    {
        $matrix = PlanFeatureCatalog::defaultMatrix()[$slug] ?? [];
        $plan = Plan::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => ucfirst($slug),
                'price' => $slug === 'free' ? 0 : 20,
                'billing_cycle' => 'monthly',
                'status' => 'active',
            ],
        );

        PlanFeature::query()->where('plan_id', $plan->id)->delete();
        foreach (PlanFeatureCatalog::keys() as $featureKey) {
            $value = $matrix[$featureKey] ?? (PlanFeatureCatalog::isBooleanKey($featureKey) ? false : 0);
            PlanFeature::query()->create([
                'plan_id' => $plan->id,
                'feature_key' => $featureKey,
                'feature_value' => PlanFeatureCatalog::normalizeValue($featureKey, $value),
                'value_type' => PlanFeatureCatalog::valueType($featureKey),
            ]);
        }

        return Tenant::query()->updateOrCreate(
            ['slug' => $tenantSlug],
            [
                'name' => 'PLG Tenant',
                'database_name' => $this->tenantDatabasePath,
                'status' => 'active',
                'plan_id' => $plan->id,
                'subscription_starts_at' => CarbonImmutable::now()->subDay(),
                'subscription_ends_at' => CarbonImmutable::now()->addDays(30),
            ],
        );
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function createTenantUserWithPermissions(array $permissionKeys): User
    {
        $role = Role::query()->create([
            'name' => 'Role '.uniqid(),
            'slug' => 'role-'.uniqid(),
        ]);
        $permissionIds = Permission::query()->whereIn('key', $permissionKeys)->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        $user = User::query()->create([
            'name' => 'PLG User',
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
    private function tenantHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->slug,
        ];
    }
}
