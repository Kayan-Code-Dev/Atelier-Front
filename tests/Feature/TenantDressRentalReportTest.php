<?php

namespace Tests\Feature;

use App\Enums\RentalReturnSettlementStatus;
use App\Enums\SecurityDepositStatus;
use App\Models\Central\PersonalAccessToken;
use App\Models\Central\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Dress;
use App\Models\Tenant\InventoryMovement;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\Permission;
use App\Models\Tenant\RentalReturnSettlement;
use App\Models\Tenant\Role;
use App\Models\Tenant\SecurityDepositTransaction;
use App\Models\Tenant\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantDressRentalReportTest extends TestCase
{
    private string $centralDatabasePath;

    private string $tenantDatabasePath;

    private Tenant $tenant;

    private User $ownerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSqliteDatabases();
        $this->runMigrations();
        $this->seedTenantPermissions();
        $this->tenant = $this->createTenant();
        $this->ownerUser = $this->createTenantUserWithPermissions([
            'dresses.view',
            'dresses.report.view',
            'customers.view',
            'invoices.view',
        ]);
    }

    public function test_authorized_owner_can_open_report(): void
    {
        $dress = $this->createDress();
        $this->actingAsTenant($this->ownerUser);

        $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.dress.id', $dress->id)
            ->assertJsonPath('data.summary.total_rentals', 0);
    }

    public function test_unauthorized_user_receives_403(): void
    {
        $dress = $this->createDress();
        $viewer = $this->createTenantUserWithPermissions(['dresses.view']);
        $this->actingAsTenant($viewer);

        $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertStatus(403);
    }

    public function test_cross_tenant_dress_access_is_blocked(): void
    {
        $dress = $this->createDress();
        $this->actingAsTenant($this->ownerUser);

        $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", [
            'Accept' => 'application/json',
            'X-Tenant' => 'unknown-tenant',
        ])->assertStatus(404);
    }

    public function test_empty_dress_history_returns_zero_totals(): void
    {
        $dress = $this->createDress();
        $this->actingAsTenant($this->ownerUser);

        $response = $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertOk();

        $response->assertJsonPath('data.summary.total_rentals', 0)
            ->assertJsonPath('data.summary.base_rental_revenue', '0.00')
            ->assertJsonPath('data.summary.total_collected', '0.00')
            ->assertJsonPath('data.summary.deposits_received', '0.00')
            ->assertJsonPath('data.rentals.meta.total', 0)
            ->assertJsonPath('data.chart', []);
    }

    public function test_completed_rental_appears_in_summary_and_history(): void
    {
        $dress = $this->createDress();
        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_RETURNED,
            'total' => 400,
            'paid_amount' => 400,
            'remaining_amount' => 0,
            'return_date' => '2026-06-10',
            'payment' => 400,
        ]);
        $this->actingAsTenant($this->ownerUser);

        $response = $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertOk();

        $response->assertJsonPath('data.summary.total_rentals', 1)
            ->assertJsonPath('data.summary.completed_rentals', 1)
            ->assertJsonPath('data.summary.base_rental_revenue', '400.00')
            ->assertJsonPath('data.summary.total_collected', '400.00')
            ->assertJsonPath('data.summary.total_outstanding', '0.00')
            ->assertJsonPath('data.rentals.data.0.rental_status', 'returned');
    }

    public function test_active_rental_appears_correctly(): void
    {
        $dress = $this->createDress();
        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_DELIVERED,
            'delivery_date' => '2026-07-10',
            'rent_start_date' => '2026-07-10',
            'rent_end_date' => '2026-12-31',
            'total' => 300,
            'paid_amount' => 100,
            'remaining_amount' => 200,
            'payment' => 100,
        ]);
        $this->actingAsTenant($this->ownerUser);

        $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.summary.active_and_upcoming_rentals', 1)
            ->assertJsonPath('data.summary.completed_rentals', 0)
            ->assertJsonPath('data.rentals.data.0.rental_status', 'active');
    }

    public function test_cancelled_rental_excluded_from_valid_totals(): void
    {
        $dress = $this->createDress();
        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_CANCELLED,
            'total' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 0,
            'payment' => 0,
        ]);
        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_RETURNED,
            'total' => 200,
            'paid_amount' => 200,
            'remaining_amount' => 0,
            'return_date' => '2026-06-08',
            'payment' => 200,
        ]);
        $this->actingAsTenant($this->ownerUser);

        $response = $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertOk();

        $response->assertJsonPath('data.summary.total_rentals', 1)
            ->assertJsonPath('data.summary.cancelled_rentals', 1)
            ->assertJsonPath('data.summary.base_rental_revenue', '200.00')
            ->assertJsonPath('data.rentals.meta.total', 2);
    }

    public function test_partial_and_full_payment_outstanding(): void
    {
        $dress = $this->createDress();
        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_PARTIALLY_PAID,
            'total' => 300,
            'paid_amount' => 100,
            'remaining_amount' => 200,
            'payment' => 100,
        ]);
        $this->actingAsTenant($this->ownerUser);

        $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.summary.total_collected', '100.00')
            ->assertJsonPath('data.summary.total_outstanding', '200.00');
    }

    public function test_settlement_fees_reported_separately_and_deposits_not_revenue(): void
    {
        $dress = $this->createDress();
        $invoice = $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_RETURNED,
            'total' => 300,
            'paid_amount' => 300,
            'remaining_amount' => 0,
            'return_date' => '2026-06-12',
            'payment' => 300,
            'security_deposit' => 500,
            'deposit_paid_amount' => 500,
        ]);

        SecurityDepositTransaction::query()->create([
            'invoice_id' => $invoice->id,
            'type' => SecurityDepositTransaction::TYPE_COLLECTED,
            'amount' => 500,
        ]);
        SecurityDepositTransaction::query()->create([
            'invoice_id' => $invoice->id,
            'type' => SecurityDepositTransaction::TYPE_REFUNDED,
            'amount' => 200,
        ]);

        RentalReturnSettlement::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'branch_id' => $invoice->branch_id,
            'expected_return_date' => '2026-06-10',
            'actual_return_date' => '2026-06-12',
            'condition' => 'damaged',
            'late_days' => 2,
            'late_fee' => 50,
            'damage_fee' => 80,
            'cleaning_fee' => 20,
            'other_fee' => 10,
            'total_fees' => 160,
            'deposit_amount' => 500,
            'deposit_paid_amount' => 500,
            'deposit_refund_amount' => 200,
            'deposit_withheld_amount' => 160,
            'additional_amount_due' => 0,
            'settlement_total' => 360,
            'status' => RentalReturnSettlementStatus::SETTLED->value,
        ]);

        $this->actingAsTenant($this->ownerUser);

        $response = $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertOk();

        $response->assertJsonPath('data.summary.base_rental_revenue', '300.00')
            ->assertJsonPath('data.summary.late_fees', '50.00')
            ->assertJsonPath('data.summary.damage_fees', '80.00')
            ->assertJsonPath('data.summary.cleaning_fees', '20.00')
            ->assertJsonPath('data.summary.additional_fees', '160.00')
            ->assertJsonPath('data.summary.deposits_received', '500.00')
            ->assertJsonPath('data.summary.deposits_returned', '200.00')
            ->assertJsonPath('data.summary.late_return_count', 1)
            ->assertJsonPath('data.summary.damage_count', 1);

        // Deposit must not inflate base revenue.
        $this->assertSame('300.00', $response->json('data.summary.base_rental_revenue'));
    }

    public function test_cancelled_payment_does_not_count_as_collected(): void
    {
        $dress = $this->createDress();
        $invoice = $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_CONFIRMED,
            'total' => 250,
            'paid_amount' => 0,
            'remaining_amount' => 250,
            'payment' => 0,
        ]);
        InvoicePayment::query()->create([
            'invoice_id' => $invoice->id,
            'amount' => 250,
            'status' => InvoicePayment::STATUS_CANCELLED,
            'payment_type' => InvoicePayment::TYPE_INVOICE_PAYMENT,
            'method' => 'cash',
        ]);
        $this->actingAsTenant($this->ownerUser);

        $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.summary.total_collected', '0.00')
            ->assertJsonPath('data.summary.total_outstanding', '250.00');
    }

    public function test_date_branch_customer_filters_and_pagination_sorting(): void
    {
        $branchA = Branch::query()->create(['name' => 'Branch A', 'status' => 'active']);
        $branchB = Branch::query()->create(['name' => 'Branch B', 'status' => 'active']);
        $customerA = Customer::query()->create(['name' => 'Alice', 'phone' => '111', 'status' => 'active']);
        $customerB = Customer::query()->create(['name' => 'Bob', 'phone' => '222', 'status' => 'active']);
        $dress = $this->createDress(['branch_id' => $branchA->id]);

        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_RETURNED,
            'branch_id' => $branchA->id,
            'customer_id' => $customerA->id,
            'rent_start_date' => '2026-01-05',
            'rent_end_date' => '2026-01-08',
            'return_date' => '2026-01-08',
            'total' => 100,
            'paid_amount' => 100,
            'remaining_amount' => 0,
            'payment' => 100,
            'invoice_number' => 'INV-A-1',
        ]);
        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_RETURNED,
            'branch_id' => $branchB->id,
            'customer_id' => $customerB->id,
            'rent_start_date' => '2026-03-01',
            'rent_end_date' => '2026-03-04',
            'return_date' => '2026-03-04',
            'total' => 200,
            'paid_amount' => 200,
            'remaining_amount' => 0,
            'payment' => 200,
            'invoice_number' => 'INV-B-2',
        ]);
        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_RETURNED,
            'branch_id' => $branchA->id,
            'customer_id' => $customerA->id,
            'rent_start_date' => '2026-05-01',
            'rent_end_date' => '2026-05-03',
            'return_date' => '2026-05-03',
            'total' => 150,
            'paid_amount' => 150,
            'remaining_amount' => 0,
            'payment' => 150,
            'invoice_number' => 'INV-A-3',
        ]);

        $this->actingAsTenant($this->ownerUser);

        $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report?".http_build_query([
            'date_from' => '2026-02-01',
            'date_to' => '2026-12-31',
            'branch_id' => $branchA->id,
            'customer_id' => $customerA->id,
            'sort' => 'rent_start_date',
            'direction' => 'asc',
            'per_page' => 1,
            'page' => 1,
        ]), $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.summary.total_rentals', 1)
            ->assertJsonPath('data.rentals.meta.total', 1)
            ->assertJsonPath('data.rentals.meta.per_page', 1)
            ->assertJsonPath('data.rentals.data.0.invoice_number', 'INV-A-3');

        $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report?".http_build_query([
            'search' => 'INV-B',
        ]), $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.rentals.meta.total', 1)
            ->assertJsonPath('data.rentals.data.0.invoice_number', 'INV-B-2');
    }

    public function test_customer_insights_repeat_rentals(): void
    {
        $dress = $this->createDress();
        $customer = Customer::query()->create(['name' => 'Repeat', 'phone' => '555', 'status' => 'active']);
        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_RETURNED,
            'customer_id' => $customer->id,
            'rent_start_date' => '2026-02-01',
            'rent_end_date' => '2026-02-03',
            'return_date' => '2026-02-03',
            'total' => 100,
            'paid_amount' => 100,
            'remaining_amount' => 0,
            'payment' => 100,
        ]);
        $this->createRentForDress($dress, [
            'status' => Invoice::STATUS_RETURNED,
            'customer_id' => $customer->id,
            'rent_start_date' => '2026-04-01',
            'rent_end_date' => '2026-04-03',
            'return_date' => '2026-04-03',
            'total' => 120,
            'paid_amount' => 120,
            'remaining_amount' => 0,
            'payment' => 120,
        ]);
        $this->actingAsTenant($this->ownerUser);

        $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report", $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.customer_insights.unique_customers', 1)
            ->assertJsonPath('data.customer_insights.repeat_customers_count', 1)
            ->assertJsonPath('data.customer_insights.customers.0.rentals_count', 2)
            ->assertJsonPath('data.customer_insights.customers.0.total_rental_value', '220.00');
    }

    public function test_branch_transfer_and_journey_order(): void
    {
        $from = Branch::query()->create(['name' => 'From', 'status' => 'active']);
        $to = Branch::query()->create(['name' => 'To', 'status' => 'active']);
        $dress = $this->createDress(['branch_id' => $to->id]);

        InventoryMovement::query()->create([
            'dress_id' => $dress->id,
            'type' => InventoryMovement::TYPE_BRANCH_TRANSFER,
            'quantity' => 1,
            'reason' => 'Branch transfer',
            'from_branch_id' => $from->id,
            'to_branch_id' => $to->id,
            'notes' => 'moved',
            'created_by' => $this->ownerUser->id,
        ]);

        $this->actingAsTenant($this->ownerUser);

        $response = $this->getJson("/api/tenant/dresses/{$dress->id}/rental-report?journey_order=desc", $this->tenantHeaders())
            ->assertOk();

        $this->assertSame('To', $response->json('data.transfers.0.to_branch.name'));
        $this->assertSame('From', $response->json('data.transfers.0.from_branch.name'));

        $journey = $response->json('data.journey');
        $this->assertNotEmpty($journey);
        $this->assertGreaterThanOrEqual(
            strtotime((string) ($journey[count($journey) - 1]['occurred_at'] ?? '1970-01-01')),
            strtotime((string) ($journey[0]['occurred_at'] ?? '1970-01-01'))
        );
    }

    public function test_multi_item_invoice_allocates_only_dress_share(): void
    {
        $dressA = $this->createDress(['code' => 'DR-A']);
        $dressB = $this->createDress(['code' => 'DR-B']);
        $customer = Customer::query()->create(['name' => 'Multi', 'status' => 'active']);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-MULTI-'.uniqid(),
            'customer_id' => $customer->id,
            'type' => Invoice::TYPE_RENT,
            'status' => Invoice::STATUS_RETURNED,
            'rent_start_date' => '2026-06-01',
            'rent_end_date' => '2026-06-05',
            'return_date' => '2026-06-05',
            'days_of_rent' => 5,
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000,
            'paid_amount' => 1000,
            'remaining_amount' => 0,
        ]);
        $invoice->items()->create(['dress_id' => $dressA->id, 'quantity' => 1, 'unit_price' => 400, 'total' => 400]);
        $invoice->items()->create(['dress_id' => $dressB->id, 'quantity' => 1, 'unit_price' => 600, 'total' => 600]);
        InvoicePayment::query()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1000,
            'status' => InvoicePayment::STATUS_PAID,
            'payment_type' => InvoicePayment::TYPE_INVOICE_PAYMENT,
            'method' => 'cash',
        ]);

        $this->actingAsTenant($this->ownerUser);

        $this->getJson("/api/tenant/dresses/{$dressA->id}/rental-report", $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.summary.base_rental_revenue', '400.00')
            ->assertJsonPath('data.summary.total_collected', '400.00');

        $this->getJson("/api/tenant/dresses/{$dressB->id}/rental-report", $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.summary.base_rental_revenue', '600.00')
            ->assertJsonPath('data.summary.total_collected', '600.00');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createDress(array $overrides = []): Dress
    {
        $dress = Dress::query()->create(array_merge([
            'code' => 'DR-RPT-'.uniqid(),
            'name' => 'Report Dress',
            'status' => Dress::STATUS_AVAILABLE,
        ], $overrides));

        if (! isset($overrides['created_at'])) {
            $dress->forceFill(['created_at' => '2025-01-01 00:00:00', 'updated_at' => '2025-01-01 00:00:00'])->save();
        }

        return $dress->refresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRentForDress(Dress $dress, array $overrides = []): Invoice
    {
        $customerId = $overrides['customer_id'] ?? Customer::query()->create([
            'name' => 'Renter '.uniqid(),
            'phone' => '050'.random_int(1000000, 9999999),
            'status' => 'active',
        ])->id;

        $invoice = Invoice::query()->create([
            'invoice_number' => $overrides['invoice_number'] ?? ('INV-RPT-'.uniqid()),
            'customer_id' => $customerId,
            'branch_id' => $overrides['branch_id'] ?? $dress->branch_id,
            'type' => Invoice::TYPE_RENT,
            'status' => $overrides['status'] ?? Invoice::STATUS_CONFIRMED,
            'rent_start_date' => $overrides['rent_start_date'] ?? '2026-06-01',
            'rent_end_date' => $overrides['rent_end_date'] ?? '2026-06-05',
            'delivery_date' => $overrides['delivery_date'] ?? null,
            'return_date' => $overrides['return_date'] ?? null,
            'days_of_rent' => $overrides['days_of_rent'] ?? 5,
            'security_deposit' => $overrides['security_deposit'] ?? 0,
            'deposit_paid_amount' => $overrides['deposit_paid_amount'] ?? 0,
            'security_deposit_status' => isset($overrides['deposit_paid_amount']) && $overrides['deposit_paid_amount'] > 0
                ? SecurityDepositStatus::HELD->value
                : null,
            'subtotal' => $overrides['total'] ?? 300,
            'discount' => $overrides['discount'] ?? 0,
            'tax' => 0,
            'total' => $overrides['total'] ?? 300,
            'paid_amount' => $overrides['paid_amount'] ?? 0,
            'remaining_amount' => $overrides['remaining_amount'] ?? ($overrides['total'] ?? 300),
            'created_by' => $this->ownerUser->id,
        ]);

        $lineTotal = (float) ($overrides['line_total'] ?? $overrides['total'] ?? 300);
        $invoice->items()->create([
            'dress_id' => $dress->id,
            'quantity' => 1,
            'unit_price' => $lineTotal,
            'total' => $lineTotal,
        ]);

        $paymentAmount = (float) ($overrides['payment'] ?? 0);
        if ($paymentAmount > 0) {
            InvoicePayment::query()->create([
                'invoice_id' => $invoice->id,
                'amount' => $paymentAmount,
                'status' => InvoicePayment::STATUS_PAID,
                'payment_type' => InvoicePayment::TYPE_INVOICE_PAYMENT,
                'method' => 'cash',
            ]);
        }

        return $invoice->refresh();
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }

        $this->centralDatabasePath = $testingPath.'/central-dress-rental-report.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-dress-rental-report.sqlite';

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
        Artisan::call('migrate:fresh', [
            '--database' => 'central',
            '--force' => true,
        ]);

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
            'name' => 'Report Tenant',
            'slug' => 'report-tenant-'.uniqid(),
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
            'name' => 'Tenant User '.uniqid(),
            'email' => uniqid().'@tenant.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    private function actingAsTenant(User $user): void
    {
        Sanctum::actingAs($user, ['*']);
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
