<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Dress;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantDressTransferTest extends TestCase
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
        $this->tenant = $this->createTenant();
        $this->user = $this->createTenantUserWithPermissions([
            'dresses.view',
            'dresses.update',
            'inventory.view',
            'inventory.manage',
        ]);
    }

    public function test_can_transfer_available_dress_between_branches(): void
    {
        $from = Branch::query()->create(['name' => 'From', 'status' => 'active']);
        $to = Branch::query()->create(['name' => 'To', 'status' => 'active']);
        $dress = Dress::query()->create([
            'code' => 'DR-TR-OK',
            'name' => 'Transferable',
            'status' => Dress::STATUS_AVAILABLE,
            'branch_id' => $from->id,
        ]);

        Sanctum::actingAs($this->user, ['*']);

        $this->postJson("/api/tenant/dresses/{$dress->id}/transfer", [
            'to_branch_id' => $to->id,
            'notes' => 'نقل للعرض',
        ], $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.branch_id', $to->id)
            ->assertJsonPath('message', 'Dress transferred');

        $this->assertDatabaseHas('dresses', [
            'id' => $dress->id,
            'branch_id' => $to->id,
        ], 'tenant');

        $this->assertDatabaseHas('inventory_movements', [
            'dress_id' => $dress->id,
            'type' => 'branch_transfer',
            'from_branch_id' => $from->id,
            'to_branch_id' => $to->id,
        ], 'tenant');
    }

    public function test_cannot_transfer_sold_dress(): void
    {
        [$dress, $to] = $this->createDressWithBranches(Dress::STATUS_SOLD);
        Sanctum::actingAs($this->user, ['*']);

        $this->postJson("/api/tenant/dresses/{$dress->id}/transfer", [
            'to_branch_id' => $to->id,
        ], $this->tenantHeaders())
            ->assertStatus(422)
            ->assertJsonPath('errors.dress.0', 'لا يمكن نقل منتج مباع.');
    }

    public function test_cannot_transfer_rented_dress(): void
    {
        [$dress, $to] = $this->createDressWithBranches(Dress::STATUS_RENTED);
        Sanctum::actingAs($this->user, ['*']);

        $this->postJson("/api/tenant/dresses/{$dress->id}/transfer", [
            'to_branch_id' => $to->id,
        ], $this->tenantHeaders())
            ->assertStatus(422)
            ->assertJsonPath('errors.dress.0', 'لا يمكن نقل منتج مؤجر حاليًا.');
    }

    public function test_cannot_transfer_dress_with_active_rental_invoice(): void
    {
        [$dress, $to] = $this->createDressWithBranches(Dress::STATUS_AVAILABLE);
        $customer = Customer::query()->create(['name' => 'Renter', 'status' => 'active']);
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-TR-'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $dress->branch_id,
            'type' => Invoice::TYPE_RENT,
            'status' => Invoice::STATUS_DELIVERED,
            'rent_start_date' => '2026-07-10',
            'rent_end_date' => '2026-12-31',
            'subtotal' => 100,
            'total' => 100,
            'paid_amount' => 100,
            'remaining_amount' => 0,
        ]);
        $invoice->items()->create([
            'dress_id' => $dress->id,
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        Sanctum::actingAs($this->user, ['*']);

        $this->postJson("/api/tenant/dresses/{$dress->id}/transfer", [
            'to_branch_id' => $to->id,
        ], $this->tenantHeaders())
            ->assertStatus(422)
            ->assertJsonPath('errors.dress.0', 'لا يمكن نقل المنتج لوجود إيجار نشط عليه.');
    }

    public function test_cannot_transfer_to_same_branch(): void
    {
        $branch = Branch::query()->create(['name' => 'Only', 'status' => 'active']);
        $dress = Dress::query()->create([
            'code' => 'DR-TR-SAME',
            'name' => 'Same',
            'status' => Dress::STATUS_AVAILABLE,
            'branch_id' => $branch->id,
        ]);
        Sanctum::actingAs($this->user, ['*']);

        $this->postJson("/api/tenant/dresses/{$dress->id}/transfer", [
            'to_branch_id' => $branch->id,
        ], $this->tenantHeaders())
            ->assertStatus(422)
            ->assertJsonPath('errors.to_branch_id.0', 'يجب اختيار فرع مختلف عن الفرع الحالي.');
    }

    /**
     * @return array{0: Dress, 1: Branch}
     */
    private function createDressWithBranches(string $status): array
    {
        $from = Branch::query()->create(['name' => 'From '.uniqid(), 'status' => 'active']);
        $to = Branch::query()->create(['name' => 'To '.uniqid(), 'status' => 'active']);
        $dress = Dress::query()->create([
            'code' => 'DR-TR-'.uniqid(),
            'name' => 'Dress',
            'status' => $status,
            'branch_id' => $from->id,
        ]);

        return [$dress, $to];
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }

        $this->centralDatabasePath = $testingPath.'/central-dress-transfer.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-dress-transfer.sqlite';
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

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Transfer Tenant',
            'slug' => 'transfer-tenant-'.uniqid(),
            'database_name' => $this->tenantDatabasePath,
            'status' => 'active',
            'subscription_starts_at' => CarbonImmutable::now()->subDay(),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(10),
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
            'name' => 'User '.uniqid(),
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
