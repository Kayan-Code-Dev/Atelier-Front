<?php

namespace Tests\Feature;

use App\Models\Tenant\Branch;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrPayrollAdjustment;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Laravel\Sanctum\Sanctum;

class TenantEmployeeBranchPayrollTest extends TenantHrTestCase
{
    public function test_employee_sees_only_assigned_branch_invoices_and_cannot_impersonate_another_employee(): void
    {
        $branchA = Branch::query()->create(['name' => 'فرع أ', 'branch_code' => 'A1', 'status' => 'active']);
        $branchB = Branch::query()->create(['name' => 'فرع ب', 'branch_code' => 'B1', 'status' => 'active']);

        $role = Role::query()->create(['name' => 'Cashier', 'slug' => 'cashier-test-'.uniqid()]);
        $permissionIds = Permission::query()
            ->whereIn('key', ['invoices.view', 'invoices.create', 'branches.view'])
            ->pluck('id')
            ->all();
        $role->permissions()->sync($permissionIds);

        $user = User::query()->create([
            'name' => 'Staff',
            'email' => 'staff-'.uniqid().'@tenant.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->roles()->sync([$role->id]);

        $employee = HrEmployee::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-BR-1',
            'full_name' => 'Staff Employee',
            'phone' => '+218910000001',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => '2024-01-01',
            'base_salary' => 1000,
            'salary_type' => 'monthly',
            'branch_id' => $branchA->id,
        ]);
        $employee->branches()->sync([$branchA->id]);

        $other = HrEmployee::query()->create([
            'employee_code' => 'EMP-BR-2',
            'full_name' => 'Other Employee',
            'phone' => '+218910000002',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => '2024-01-01',
            'base_salary' => 1000,
            'salary_type' => 'monthly',
            'branch_id' => $branchB->id,
        ]);

        Invoice::query()->create([
            'invoice_number' => 'INV-A-1',
            'type' => Invoice::TYPE_SELL,
            'status' => Invoice::STATUS_CONFIRMED,
            'total' => 50,
            'remaining_amount' => 50,
            'branch_id' => $branchA->id,
            'employee_id' => $employee->id,
        ]);
        Invoice::query()->create([
            'invoice_number' => 'INV-B-1',
            'type' => Invoice::TYPE_SELL,
            'status' => Invoice::STATUS_CONFIRMED,
            'total' => 80,
            'remaining_amount' => 80,
            'branch_id' => $branchB->id,
            'employee_id' => $other->id,
        ]);

        Sanctum::actingAs($user, ['*']);
        $headers = $this->tenantHeaders();

        $this->getJson('/api/tenant/invoices', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.invoice_number', 'INV-A-1');

        $this->getJson('/api/tenant/branches', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $created = $this->postJson('/api/tenant/invoices', [
            'type' => Invoice::TYPE_SELL,
            'status' => Invoice::STATUS_CONFIRMED,
            'branch_id' => $branchB->id,
            'employee_id' => $other->id,
            'items' => [
                ['description' => 'Item', 'quantity' => 1, 'unit_price' => 25],
            ],
        ], $headers)->assertCreated();

        $this->assertSame($employee->id, (int) $created->json('data.employee_id'));
        $this->assertSame($branchA->id, (int) $created->json('data.branch_id'));
    }

    public function test_multi_branch_employee_must_choose_branch_on_invoice(): void
    {
        $branchA = Branch::query()->create(['name' => 'أ', 'branch_code' => 'MA', 'status' => 'active']);
        $branchB = Branch::query()->create(['name' => 'ب', 'branch_code' => 'MB', 'status' => 'active']);

        $role = Role::query()->create(['name' => 'Multi', 'slug' => 'multi-'.uniqid()]);
        $role->permissions()->sync(Permission::query()->whereIn('key', ['invoices.view', 'invoices.create'])->pluck('id'));

        $user = User::query()->create([
            'name' => 'Multi Staff',
            'email' => 'multi-'.uniqid().'@tenant.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->roles()->sync([$role->id]);

        $employee = HrEmployee::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-MULTI',
            'full_name' => 'Multi Employee',
            'phone' => '+218910000009',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => '2024-01-01',
            'base_salary' => 1000,
            'salary_type' => 'monthly',
            'branch_id' => $branchA->id,
        ]);
        $employee->branches()->sync([$branchA->id, $branchB->id]);

        Sanctum::actingAs($user, ['*']);
        $headers = $this->tenantHeaders();

        $this->postJson('/api/tenant/invoices', [
            'type' => Invoice::TYPE_SELL,
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ], $headers)->assertStatus(422);

        $this->postJson('/api/tenant/invoices', [
            'type' => Invoice::TYPE_SELL,
            'branch_id' => $branchB->id,
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.branch_id', $branchB->id)
            ->assertJsonPath('data.employee_id', $employee->id);
    }

    public function test_payroll_commission_percentage_and_fixed_times_invoice_count(): void
    {
        $user = $this->createTenantUserWithPermissions(['hr.dashboard.view', 'hr.view']);
        $headers = $this->authHeaders($user);

        $percentEmp = HrEmployee::query()->create([
            'employee_code' => 'EMP-PCT',
            'full_name' => 'Percent Emp',
            'phone' => '+218910000011',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => '2024-01-01',
            'base_salary' => 1000,
            'salary_type' => 'monthly',
            'commission_type' => 'percentage',
            'commission_rate' => 10,
            'commission_activity' => 'all',
        ]);
        $fixedEmp = HrEmployee::query()->create([
            'employee_code' => 'EMP-FIX',
            'full_name' => 'Fixed Emp',
            'phone' => '+218910000012',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => '2024-01-01',
            'base_salary' => 2000,
            'salary_type' => 'monthly',
            'commission_type' => 'fixed',
            'commission_fixed_amount' => 15,
            'commission_activity' => 'all',
        ]);

        Invoice::query()->create([
            'invoice_number' => 'INV-P1',
            'type' => Invoice::TYPE_SELL,
            'status' => Invoice::STATUS_CONFIRMED,
            'total' => 100,
            'remaining_amount' => 0,
            'employee_id' => $percentEmp->id,
        ]);
        Invoice::query()->create([
            'invoice_number' => 'INV-P2',
            'type' => Invoice::TYPE_RENT,
            'status' => Invoice::STATUS_PAID,
            'total' => 50,
            'remaining_amount' => 0,
            'employee_id' => $percentEmp->id,
        ]);
        Invoice::query()->create([
            'invoice_number' => 'INV-F1',
            'type' => Invoice::TYPE_SELL,
            'status' => Invoice::STATUS_CONFIRMED,
            'total' => 200,
            'remaining_amount' => 0,
            'employee_id' => $fixedEmp->id,
        ]);
        Invoice::query()->create([
            'invoice_number' => 'INV-F2',
            'type' => Invoice::TYPE_SELL,
            'status' => Invoice::STATUS_CONFIRMED,
            'total' => 300,
            'remaining_amount' => 0,
            'employee_id' => $fixedEmp->id,
        ]);

        HrPayrollAdjustment::query()->create([
            'employee_id' => $percentEmp->id,
            'type' => HrPayrollAdjustment::TYPE_BONUS,
            'amount' => 40,
            'effective_month' => now()->startOfMonth()->toDateString(),
            'status' => 'approved',
        ]);
        HrPayrollAdjustment::query()->create([
            'employee_id' => $percentEmp->id,
            'type' => HrPayrollAdjustment::TYPE_DEDUCTION,
            'amount' => 25,
            'effective_month' => now()->startOfMonth()->toDateString(),
            'status' => 'approved',
        ]);

        $dashboard = $this->getJson('/api/tenant/hr/dashboard', $headers)->assertOk();
        $this->assertEquals(45.0, (float) $dashboard->json('data.payroll_summary.commissions'));
        $this->assertEquals(40.0, (float) $dashboard->json('data.payroll_summary.bonuses'));
        $this->assertEquals(25.0, (float) $dashboard->json('data.payroll_summary.deductions'));
        $this->assertEquals(3000.0, (float) $dashboard->json('data.payroll_summary.gross_salaries'));
        $this->assertEquals(3060.0, (float) $dashboard->json('data.payroll_summary.net_payroll'));
    }

    public function test_sale_invoice_from_employee_account_accrues_percentage_commission(): void
    {
        $role = Role::query()->create(['name' => 'Seller', 'slug' => 'seller-'.uniqid()]);
        $role->permissions()->sync(Permission::query()->whereIn('key', [
            'invoices.view', 'invoices.create', 'hr.dashboard.view', 'hr.view',
        ])->pluck('id'));

        $user = User::query()->create([
            'name' => 'Seller Staff',
            'email' => 'seller-'.uniqid().'@tenant.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->roles()->sync([$role->id]);

        $employee = HrEmployee::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-SELL',
            'full_name' => 'Seller Staff',
            'phone' => '+218910000077',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => '2024-01-01',
            'base_salary' => 1000,
            'salary_type' => 'monthly',
            'commission_type' => 'percentage',
            'commission_rate' => 10,
            'commission_activity' => 'sale',
        ]);

        $customer = \App\Models\Tenant\Customer::query()->create([
            'name' => 'Sale Client',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user, ['*']);
        $created = $this->postJson('/api/tenant/sales/invoices', [
            'customer_id' => $customer->id,
            'items' => [
                ['description' => 'فستان', 'quantity' => 1, 'unit_price' => 200],
            ],
        ], $this->tenantHeaders())->assertCreated();

        $this->assertSame($employee->id, (int) $created->json('data.employee_id'));

        $admin = $this->createTenantUserWithPermissions(['hr.dashboard.view', 'hr.view']);
        $payroll = $this->getJson('/api/tenant/hr/payroll?month='.now()->format('Y-m'), $this->authHeaders($admin))
            ->assertOk();
        $row = collect($payroll->json('data.rows'))->firstWhere('employee_id', $employee->id);
        $this->assertNotNull($row);
        $this->assertEquals(20.0, (float) $row['commissions']);
        $this->assertEquals(1020.0, (float) $row['net_salary']);
    }
}
