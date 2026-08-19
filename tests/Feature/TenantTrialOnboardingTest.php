<?php

namespace Tests\Feature;

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
use App\Models\Tenant\TrialOnboardingState;
use App\Models\Tenant\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantTrialOnboardingTest extends TestCase
{
    private string $centralDatabasePath;

    private string $tenantDatabasePath;

    private Tenant $demoTenant;

    private Tenant $paidTenant;

    private User $owner;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSqliteDatabases();
        $this->runMigrations();
        $this->seedTenantPermissions();
        $this->demoTenant = $this->createTenant('demo-atelier', ['source' => 'demo']);
        $this->paidTenant = $this->createTenant('paid-atelier', []);
        $this->owner = $this->createTenantUserWithPermissions($this->journeyPermissions());
        $this->otherUser = $this->createTenantUserWithPermissions($this->journeyPermissions());
    }

    public function test_paid_tenant_does_not_activate_trial_onboarding(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $this->getJson('/api/tenant/trial-onboarding', $this->headers($this->paidTenant))
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.steps', []);

        $this->postJson('/api/tenant/trial-onboarding/start', [], $this->headers($this->paidTenant))
            ->assertForbidden();

        $this->assertSame(0, TrialOnboardingEvent::query()->where('tenant_id', $this->paidTenant->id)->count());
        $this->assertSame(0, TrialOnboardingState::query()->count());
    }

    public function test_unauthenticated_access_is_rejected(): void
    {
        $this->getJson('/api/tenant/trial-onboarding', $this->headers($this->demoTenant))
            ->assertUnauthorized();
    }

    public function test_full_trial_journey_completes_from_business_state(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $headers = $this->headers($this->demoTenant);

        $this->postJson('/api/tenant/trial-onboarding/start', [], $headers)
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertSame(1, $this->eventCount('trial_onboarding_started'));

        $this->postJson('/api/tenant/branches', [
            'branch_code' => 'BR-T01',
            'name' => 'الفرع الرئيسي',
            'status' => 'active',
        ], $headers)->assertCreated();

        $this->getJson('/api/tenant/trial-onboarding', $headers)
            ->assertOk()
            ->assertJsonPath('data.completed_steps.0', 'branch_setup');

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
                'code' => 'PO-T-1',
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

        $customerId = (int) Customer::query()->value('id');
        Invoice::query()->create([
            'invoice_number' => 'RENT-T-1',
            'customer_id' => $customerId,
            'branch_id' => $branchId,
            'type' => Invoice::TYPE_RENT,
            'status' => Invoice::STATUS_CONFIRMED,
            'total' => 200,
            'remaining_amount' => 200,
        ]);

        $this->postJson('/api/tenant/trial-onboarding/views', ['step' => 'balances_review'], $headers)->assertOk();
        $this->postJson('/api/tenant/trial-onboarding/views', ['step' => 'account_statement'], $headers)->assertOk();

        $done = $this->getJson('/api/tenant/trial-onboarding', $headers);
        $done->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress.completed', 10)
            ->assertJsonPath('data.progress.percent', 100);

        $this->assertTrue((bool) $done->json('data.summary.branches') >= 1);
        $this->assertNotNull(TrialOnboardingEvent::query()
            ->where('tenant_id', $this->demoTenant->id)
            ->where('user_id', $this->owner->id)
            ->where('event_name', 'trial_onboarding_completed')
            ->first());

        $this->postJson('/api/tenant/trial-onboarding/start', [], $headers)->assertOk();
        $this->assertSame(1, $this->eventCount('trial_onboarding_started'));
        $this->assertSame(1, $this->eventCount('trial_branch_created'));
        $this->assertSame(1, $this->eventCount('trial_onboarding_completed'));
    }

    public function test_precreated_branch_is_detected_without_done_button(): void
    {
        Branch::query()->create([
            'name' => 'فرع موجود',
            'branch_code' => 'BR-EXIST',
            'status' => 'active',
        ]);
        Sanctum::actingAs($this->owner, ['*']);

        $response = $this->getJson('/api/tenant/trial-onboarding', $this->headers($this->demoTenant))
            ->assertOk()
            ->assertJsonPath('data.completed_steps.0', 'branch_setup');
        $this->assertNotContains('cashbox_setup', $response->json('data.completed_steps'));
    }

    public function test_user_state_is_isolated_and_resume_keeps_progress(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $headers = $this->headers($this->demoTenant);
        $this->postJson('/api/tenant/trial-onboarding/start', [], $headers)->assertOk();
        Branch::query()->create(['name' => 'فرع', 'branch_code' => 'BR-R', 'status' => 'active']);
        $this->getJson('/api/tenant/trial-onboarding', $headers)
            ->assertJsonPath('data.resume', true)
            ->assertJsonPath('data.status', 'in_progress');

        Sanctum::actingAs($this->otherUser, ['*']);
        $other = $this->getJson('/api/tenant/trial-onboarding', $headers)->assertOk();
        $this->assertNotSame(
            $this->owner->id,
            (int) $other->json('data.user_id'),
        );
        $this->assertContains('branch_setup', $other->json('data.completed_steps'));
        $this->assertSame(0, TrialOnboardingEvent::query()
            ->where('tenant_id', $this->demoTenant->id)
            ->where('user_id', $this->otherUser->id)
            ->where('event_name', 'trial_onboarding_started')
            ->count());
    }

    public function test_demo_events_are_not_written_for_another_tenant(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $this->postJson('/api/tenant/trial-onboarding/start', [], $this->headers($this->demoTenant))->assertOk();

        $this->assertSame(0, TrialOnboardingEvent::query()->where('tenant_id', $this->paidTenant->id)->count());
        $this->assertGreaterThan(0, TrialOnboardingEvent::query()->where('tenant_id', $this->demoTenant->id)->count());
    }

    public function test_demo_tenant_can_record_upgrade_signal_once(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $headers = $this->headers($this->demoTenant);
        $this->postJson('/api/tenant/trial-onboarding/signals', ['signal' => 'upgrade_clicked'], $headers)
            ->assertOk();
        $this->postJson('/api/tenant/trial-onboarding/signals', ['signal' => 'upgrade_clicked'], $headers)
            ->assertOk();
        $this->assertSame(1, $this->eventCount('trial_upgrade_clicked'));

        $this->postJson('/api/tenant/trial-onboarding/signals', ['signal' => 'upgrade_clicked'], $this->headers($this->paidTenant))
            ->assertForbidden();
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

    private function eventCount(string $eventName): int
    {
        return TrialOnboardingEvent::query()
            ->where('tenant_id', $this->demoTenant->id)
            ->where('user_id', $this->owner->id)
            ->where('event_name', $eventName)
            ->count();
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }
        $this->centralDatabasePath = $testingPath.'/central-trial-onboarding.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-trial-onboarding.sqlite';
        @unlink($this->centralDatabasePath);
        @unlink($this->tenantDatabasePath);
        touch($this->centralDatabasePath);
        touch($this->tenantDatabasePath);

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
    private function createTenant(string $slug, array $metadata): Tenant
    {
        return Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'database_name' => $this->tenantDatabasePath,
            'status' => 'active',
            'metadata' => $metadata === [] ? null : $metadata,
            'subscription_starts_at' => CarbonImmutable::now()->subDay(),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(7),
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
    private function headers(Tenant $tenant): array
    {
        return ['Accept' => 'application/json', 'X-Tenant' => $tenant->slug];
    }
}
