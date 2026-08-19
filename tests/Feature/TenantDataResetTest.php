<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Models\Central\TenantProvisioningLog;
use App\Models\Tenant\Account;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Services\Tenant\TenantUserDirectoryService;
use Carbon\CarbonImmutable;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantDataResetTest extends TestCase
{
    private string $centralDatabasePath;

    private string $tenantDatabasePath;

    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSqliteDatabases();
        $this->runMigrations();
        $this->seedTenantPermissions();
        $this->tenant = $this->createTenant('atelier-reset');
        $this->owner = $this->createOwner('owner@reset.test');
        app(TenantUserDirectoryService::class)->register($this->tenant, 'owner@reset.test');
    }

    public function test_preview_lists_operational_data_for_owner(): void
    {
        $this->seedOperationalData();
        Sanctum::actingAs($this->owner, ['*']);

        $response = $this->getJson('/api/tenant/settings/data-reset/preview', $this->tenantHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.confirmation_word', 'تصفير');

        $this->assertGreaterThan(0, (int) $response->json('data.total_records'));
        $labels = collect($response->json('data.categories'))->pluck('label')->all();
        $this->assertTrue(collect($labels)->contains(fn ($label) => str_contains((string) $label, 'العملاء')));
    }

    public function test_owner_can_reset_tenant_data_and_keep_login(): void
    {
        $this->seedOperationalData();
        $staff = $this->createStaff('staff@reset.test');
        Sanctum::actingAs($this->owner, ['*']);

        $response = $this->postJson('/api/tenant/settings/data-reset', [
            'password' => 'secret123',
            'confirmation' => 'تصفير',
        ], $this->tenantHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, Customer::withTrashed()->count());
        $this->assertSame(0, Branch::withTrashed()->count());
        $this->assertDatabaseHas('users', ['email' => 'owner@reset.test'], 'tenant');
        $this->assertDatabaseHas('users', ['email' => 'staff@reset.test'], 'tenant');
        $this->assertNotNull(Account::query()->where('code', '1000')->first());
        $this->assertTrue($this->owner->fresh()->isOwner());
        $this->assertTrue(
            Permission::query()->where('key', 'settings.data_reset')->exists()
        );

        $this->assertSame(
            1,
            TenantProvisioningLog::query()
                ->where('tenant_id', $this->tenant->id)
                ->where('step', 'tenant_data_reset')
                ->count()
        );

        $login = $this->postJson('/api/tenant/login', [
            'email' => 'owner@reset.test',
            'password' => 'secret123',
        ], ['Accept' => 'application/json']);

        $login->assertOk()
            ->assertJsonPath('data.user.email', 'owner@reset.test');
        $this->assertNotEmpty($login->json('data.token'));
    }

    public function test_reset_requires_password_and_confirmation_word(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        $this->postJson('/api/tenant/settings/data-reset', [
            'password' => 'wrong-password',
            'confirmation' => 'تصفير',
        ], $this->tenantHeaders())
            ->assertStatus(422);

        $this->postJson('/api/tenant/settings/data-reset', [
            'password' => 'secret123',
            'confirmation' => 'نعم',
        ], $this->tenantHeaders())
            ->assertStatus(422)
            ->assertJsonPath('message', 'اكتب كلمة «تصفير» للتأكيد');
    }

    public function test_non_owner_cannot_reset_tenant_data(): void
    {
        $manager = $this->createTenantUserWithPermissions([
            'settings.manage',
            'settings.data_reset',
        ]);
        Sanctum::actingAs($manager, ['*']);

        $this->getJson('/api/tenant/settings/data-reset/preview', $this->tenantHeaders())
            ->assertForbidden();

        $this->postJson('/api/tenant/settings/data-reset', [
            'password' => 'password',
            'confirmation' => 'تصفير',
        ], $this->tenantHeaders())
            ->assertForbidden();
    }

    public function test_reset_does_not_touch_another_tenant_database(): void
    {
        $this->seedOperationalData();

        $otherPath = storage_path('framework/testing/tenant-data-reset-other.sqlite');
        $this->prepareSecondaryTenantDatabase($otherPath);
        $otherTenant = Tenant::query()->create([
            'name' => 'Other Atelier',
            'slug' => 'other-reset',
            'database_name' => $otherPath,
            'status' => 'active',
            'subscription_starts_at' => CarbonImmutable::now()->subDay(),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(10),
        ]);

        Config::set('database.connections.tenant.database', $otherPath);
        DB::purge('tenant');
        DB::reconnect('tenant');
        Customer::query()->create(['name' => 'Other Customer', 'status' => 'active']);

        Config::set('database.connections.tenant.database', $this->tenantDatabasePath);
        DB::purge('tenant');
        DB::reconnect('tenant');

        Sanctum::actingAs($this->owner, ['*']);
        $this->postJson('/api/tenant/settings/data-reset', [
            'password' => 'secret123',
            'confirmation' => 'تصفير',
        ], $this->tenantHeaders())->assertOk();

        $this->assertSame(0, Customer::withTrashed()->count());

        Config::set('database.connections.tenant.database', $otherPath);
        DB::purge('tenant');
        DB::reconnect('tenant');
        $this->assertSame(1, Customer::query()->count());
        $this->assertSame('other-reset', $otherTenant->slug);
    }

    private function seedOperationalData(): void
    {
        Customer::query()->create([
            'name' => 'Will Be Deleted',
            'phone' => '0100000000',
            'status' => 'active',
        ]);
        Branch::query()->create([
            'name' => 'Main Branch',
            'status' => 'active',
        ]);
    }

    private function createOwner(string $email): User
    {
        $user = User::query()->create([
            'name' => 'Owner User',
            'email' => $email,
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $ownerRole = Role::query()->where('slug', 'owner')->firstOrFail();
        $user->roles()->sync([$ownerRole->id]);

        return $user;
    }

    private function createStaff(string $email): User
    {
        return User::query()->create([
            'name' => 'Staff User',
            'email' => $email,
            'password' => 'staff-pass',
            'status' => 'active',
        ]);
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
            'name' => 'Restricted User',
            'email' => uniqid().'@tenant.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }

        $this->centralDatabasePath = $testingPath.'/central-data-reset.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-data-reset.sqlite';
        @unlink($this->centralDatabasePath);
        @unlink($this->tenantDatabasePath);
        touch($this->centralDatabasePath);
        touch($this->tenantDatabasePath);

        Config::set('database.default', 'central');
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

        DB::purge('central');
        DB::purge('tenant');
    }

    private function runMigrations(): void
    {
        Artisan::call('migrate:fresh', ['--database' => 'central', '--force' => true]);
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

    private function createTenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Reset Tenant',
            'slug' => $slug,
            'database_name' => $this->tenantDatabasePath,
            'status' => 'active',
            'subscription_starts_at' => CarbonImmutable::now()->subDay(),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(10),
        ]);
    }

    private function prepareSecondaryTenantDatabase(string $databasePath): void
    {
        @unlink($databasePath);
        touch($databasePath);

        Config::set('database.connections.tenant.database', $databasePath);
        DB::purge('tenant');
        DB::reconnect('tenant');

        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => base_path('database/migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);
        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => TenantRolePermissionSeeder::class,
            '--force' => true,
        ]);

        Config::set('database.connections.tenant.database', $this->tenantDatabasePath);
        DB::purge('tenant');
        DB::reconnect('tenant');
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
