<?php

namespace Tests\Feature;

use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrEmployeeNote;
use App\Models\Tenant\HrPayrollAdjustment;
use Carbon\Carbon;

class HrEmployeeShowExtrasTest extends TenantHrTestCase
{
    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $user = $this->createTenantUserWithPermissions([
            ...$this->allHrPhase1Permissions(),
            'hr.leaves.view',
            'hr.leaves.create',
            'hr.leaves.status',
        ]);

        return $this->authHeaders($user);
    }

    public function test_payroll_history_starts_at_joining_month(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');
        $headers = $this->headers();
        $employee = HrEmployee::query()->create([
            'employee_code' => 'JOIN-01',
            'full_name' => 'Joined In June',
            'phone' => '+218910000701',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => '2026-06-10',
            'base_salary' => 2000,
            'salary_type' => 'monthly',
        ]);

        $history = $this->getJson('/api/tenant/hr/payroll/employees/'.$employee->id.'/history?months=12', $headers)
            ->assertOk()
            ->json('data');

        $months = collect($history)->pluck('month')->all();
        $this->assertSame(['2026-08', '2026-07', '2026-06'], $months);
        Carbon::setTestNow();
    }

    public function test_employee_notes_are_real_and_scoped(): void
    {
        $headers = $this->headers();
        $employee = HrEmployee::query()->create([
            'employee_code' => 'NOTE-01',
            'full_name' => 'Notes Employee',
            'phone' => '+218910000702',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => '2026-01-01',
            'base_salary' => 1500,
            'salary_type' => 'monthly',
        ]);

        $this->postJson('/api/tenant/hr/employees/'.$employee->id.'/notes', [
            'type' => 'warning',
            'content' => 'تأخر متكرر هذا الأسبوع',
        ], $headers)->assertCreated()->assertJsonPath('data.content', 'تأخر متكرر هذا الأسبوع');

        $this->getJson('/api/tenant/hr/employees/'.$employee->id.'/notes', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->assertDatabaseHas('hr_employee_notes', [
            'employee_id' => $employee->id,
            'type' => 'warning',
        ], 'tenant');
        $this->assertSame(1, HrEmployeeNote::query()->count());
    }

    public function test_paid_leave_creates_payroll_deduction_on_approve(): void
    {
        $headers = $this->headers();
        $employee = HrEmployee::query()->create([
            'employee_code' => 'LEAVE-01',
            'full_name' => 'Leave Employee',
            'phone' => '+218910000703',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => '2026-01-01',
            'base_salary' => 3000,
            'salary_type' => 'monthly',
        ]);

        $created = $this->postJson('/api/tenant/hr/leaves', [
            'employee_id' => $employee->id,
            'type' => 'annual',
            'from_date' => '2026-08-10',
            'to_date' => '2026-08-12',
            'reason' => 'إجازة سنوية',
            'is_paid' => true,
            'deduction_amount' => 120,
        ], $headers)->assertCreated();

        $leaveId = (int) $created->json('data.id');
        $this->assertTrue((bool) $created->json('data.is_paid'));
        $this->assertSame(120, (int) $created->json('data.deduction_amount'));

        $this->patchJson('/api/tenant/hr/leaves/'.$leaveId.'/status', [
            'status' => 'approved',
        ], $headers)->assertOk();

        $this->assertDatabaseHas('hr_payroll_adjustments', [
            'employee_id' => $employee->id,
            'leave_request_id' => $leaveId,
            'type' => HrPayrollAdjustment::TYPE_DEDUCTION,
            'amount' => 120,
        ], 'tenant');
    }
}
