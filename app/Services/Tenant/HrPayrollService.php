<?php

namespace App\Services\Tenant;

use App\Enums\HrCommissionActivity;
use App\Enums\HrCommissionType;
use App\Enums\HrEmployeeStatus;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrPayrollAdjustment;
use App\Models\Tenant\HrPayrollPayment;
use App\Models\Tenant\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class HrPayrollService
{
    public function __construct(
        private readonly HrSettingService $settingService,
        private readonly HrMetricsService $metricsService,
    ) {}

    /**
     * @return array{summary: array<string, float|int>, rows: list<array<string, mixed>>}
     */
    public function payrollForMonth(string $month, ?int $branchId = null): array
    {
        $period = Carbon::parse($month.'-01');
        $rules = $this->settingService->all()['payroll_rules'] ?? [];

        $employees = HrEmployee::query()
            ->with(['branch', 'department'])
            ->where('status', HrEmployeeStatus::ACTIVE->value)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId));
        app(\App\Support\Tenant\AuthorizedBranchAccess::class)->constrainEmployeeQuery($employees);
        $employees = $employees->orderBy('full_name')->get();

        $rows = [];
        foreach ($employees as $employee) {
            $rows[] = $this->buildPayrollRow($employee, $period, $rules);
        }

        return [
            'summary' => [
                'gross' => round(collect($rows)->sum('base_salary'), 2),
                'deductions' => round(collect($rows)->sum(fn (array $r) => $r['deductions'] + $r['advances']), 2),
                'bonuses' => round(collect($rows)->sum('bonuses'), 2),
                'commissions' => round(collect($rows)->sum('commissions'), 2),
                'net' => round(collect($rows)->sum('net_salary'), 2),
                'employee_count' => count($rows),
                'paid_count' => collect($rows)->where('status', 'paid')->count(),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payrollRowForEmployee(HrEmployee $employee, string $month): array
    {
        $period = Carbon::parse($month.'-01');
        $rules = $this->settingService->all()['payroll_rules'] ?? [];

        return $this->buildPayrollRow($employee->loadMissing(['branch', 'department']), $period, $rules);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function payrollHistoryForEmployee(HrEmployee $employee, int $months = 12): array
    {
        $months = max(1, min(24, $months));
        $end = now()->copy()->startOfMonth();
        $start = $employee->joining_date
            ? Carbon::parse($employee->joining_date)->startOfMonth()
            : $end->copy()->subMonths($months - 1);

        if ($start->gt($end)) {
            $start = $end->copy();
        }

        $cappedStart = $end->copy()->subMonths($months - 1);
        if ($start->lt($cappedStart)) {
            $start = $cappedStart;
        }

        $rows = [];
        $cursor = $end->copy();
        $rules = $this->settingService->all()['payroll_rules'] ?? [];
        while ($cursor->gte($start)) {
            $rows[] = $this->buildPayrollRow($employee, $cursor->copy(), $rules);
            $cursor->subMonth();
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function payslipForEmployee(HrEmployee $employee, string $month): array
    {
        $period = Carbon::parse($month.'-01');
        $rules = $this->settingService->all()['payroll_rules'] ?? [];
        $row = $this->buildPayrollRow($employee, $period, $rules);

        return array_merge($row, [
            'period_type' => 'month',
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'department' => $employee->department?->name,
                'job_title' => $employee->jobTitle?->name,
                'branch_name' => $employee->branch?->name,
            ],
            'attendance' => $this->metricsService->employeeMonthAttendance($employee, $period),
            'leaves' => $this->metricsService->employeeMonthLeaves($employee, $period),
            'adjustment_lines' => $this->adjustmentLines($employee->id, $period),
            'commission_breakdown' => $this->commissionBreakdown($employee, $period),
        ]);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function buildPayrollRow(HrEmployee $employee, Carbon $period, array $rules): array
    {
        $this->backfillInvoiceCommissions($employee, $period);
        $monthStart = $period->copy()->startOfMonth()->toDateString();
        $monthEnd = $period->copy()->endOfMonth()->toDateString();
        $monthKey = $period->format('Y-m');

        $attendance = $employee->attendanceRecords()
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get();

        $presentDays = $attendance->whereIn('status', ['present', 'late', 'half_day'])->count();
        $absentDays = $attendance->where('status', 'absent')->count();
        $lateDays = $attendance->where('status', 'late')->count();
        $overtimeHours = (float) $attendance->sum('overtime_hours');

        $baseSalary = (float) $employee->base_salary;
        $workingDays = max(1, $period->daysInMonth);
        $dailyRate = $baseSalary / $workingDays;

        $attendanceDeduction = 0.0;
        if (! empty($rules['absence_deducts_daily_rate'])) {
            $attendanceDeduction += $absentDays * $dailyRate;
        }
        if (! empty($rules['late_deduction_enabled']) && (float) ($rules['late_deduction_per_minute'] ?? 0) > 0) {
            $lateMinutes = (int) $attendance->sum('late_minutes');
            $attendanceDeduction += $lateMinutes * (float) $rules['late_deduction_per_minute'];
        }

        $overtimePay = 0.0;
        if (! empty($rules['overtime_enabled']) && $overtimeHours > 0) {
            $hourly = $dailyRate / max(1, (float) $employee->working_hours_per_day);
            $overtimePay = $overtimeHours * $hourly * (float) ($rules['overtime_rate_multiplier'] ?? 1.5);
        }

        $adjustments = HrPayrollAdjustment::query()
            ->where('employee_id', $employee->id)
            ->whereDate('effective_month', $monthKey.'-01')
            ->where('status', 'approved')
            ->get();

        $advances = (float) $adjustments->where('type', HrPayrollAdjustment::TYPE_ADVANCE)->sum('amount');
        $manualDeductions = (float) $adjustments->where('type', HrPayrollAdjustment::TYPE_DEDUCTION)->sum('amount');
        $bonuses = (float) $adjustments->where('type', HrPayrollAdjustment::TYPE_BONUS)->sum('amount');
        $manualCommissions = (float) $adjustments
            ->where('type', HrPayrollAdjustment::TYPE_COMMISSION)
            ->filter(fn (HrPayrollAdjustment $row): bool => $row->invoice_id === null)
            ->sum('amount');
        $accruedCommissions = (float) $adjustments
            ->where('type', HrPayrollAdjustment::TYPE_COMMISSION)
            ->filter(fn (HrPayrollAdjustment $row): bool => $row->invoice_id !== null)
            ->sum('amount');

        $commissionBreakdown = $this->commissionBreakdown($employee, $period);
        $activityCommissions = max(
            (float) ($commissionBreakdown['total'] ?? 0),
            $accruedCommissions,
        );

        $commissions = $manualCommissions + $activityCommissions;
        $deductions = $attendanceDeduction + $manualDeductions;
        $net = max(0, $baseSalary + $overtimePay + $bonuses + $commissions - $deductions - $advances);

        $payment = HrPayrollPayment::query()
            ->where('employee_id', $employee->id)
            ->whereDate('payroll_month', $monthKey.'-01')
            ->first();

        return [
            'id' => $employee->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'employee_code' => $employee->employee_code,
            'branch_name' => $employee->branch?->name ?? '',
            'base_salary' => round($baseSalary, 2),
            'attendance_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'overtime' => round($overtimePay, 2),
            'advances' => round($advances, 2),
            'deductions' => round($deductions, 2),
            'bonuses' => round($bonuses, 2),
            'commissions' => round($commissions, 2),
            'activity_commissions' => round($activityCommissions, 2),
            'manual_commissions' => round($manualCommissions, 2),
            'net_salary' => round($net, 2),
            'status' => $payment ? 'paid' : 'draft',
            'payment_id' => $payment?->id,
            'paid_at' => $payment?->paid_at?->toISOString(),
            'month' => $monthKey,
        ];
    }

    /**
     * @return array{fixed: float, percentage: float, activity_total: float, rate: float, total: float, invoice_count: int}
     */
    private function commissionBreakdown(HrEmployee $employee, Carbon $period, string $scope = 'month'): array
    {
        $type = (string) ($employee->commission_type ?? HrCommissionType::NONE->value);
        $fixed = 0.0;
        $percentage = 0.0;
        $activityTotal = 0.0;
        $invoiceCount = 0;
        $rate = (float) ($employee->commission_rate ?? 0);
        $fixedAmount = (float) ($employee->commission_fixed_amount ?? 0);

        if ($type !== HrCommissionType::NONE->value) {
            [$activityTotal, $invoiceCount] = $this->activityTotalsForEmployee($employee, $period, $scope);
        }

        if (in_array($type, [HrCommissionType::FIXED->value, HrCommissionType::MIXED->value], true)) {
            $fixed = round($fixedAmount * $invoiceCount, 2);
        }

        if (
            in_array($type, [HrCommissionType::PERCENTAGE->value, HrCommissionType::MIXED->value], true)
            && $rate > 0
        ) {
            $percentage = round($activityTotal * ($rate / 100), 2);
        }

        return [
            'fixed' => round($fixed, 2),
            'percentage' => $percentage,
            'activity_total' => round($activityTotal, 2),
            'invoice_count' => $invoiceCount,
            'rate' => $rate,
            'total' => round($fixed + $percentage, 2),
        ];
    }

    /**
     * @return array{0: float, 1: int}
     */
    private function activityTotalsForEmployee(HrEmployee $employee, Carbon $period, string $scope = 'month'): array
    {
        $activity = (string) ($employee->commission_activity ?? HrCommissionActivity::ALL->value);
        $types = match ($activity) {
            HrCommissionActivity::SALE->value => [Invoice::TYPE_SELL],
            HrCommissionActivity::RENT->value => [Invoice::TYPE_RENT],
            HrCommissionActivity::TAILORING->value => [Invoice::TYPE_TAILORING],
            default => [Invoice::TYPE_SELL, Invoice::TYPE_RENT, Invoice::TYPE_TAILORING],
        };

        if ($scope === 'year') {
            $start = $period->copy()->startOfYear();
            $end = $period->copy()->endOfYear();
        } else {
            $start = $period->copy()->startOfMonth();
            $end = $period->copy()->endOfMonth();
        }

        $query = Invoice::query()
            ->whereIn('type', $types)
            ->whereNotIn('status', [Invoice::STATUS_CANCELLED, Invoice::STATUS_DRAFT])
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($q) use ($employee): void {
                $q->where('employee_id', $employee->id);
                if ($employee->user_id) {
                    $q->orWhere('created_by', $employee->user_id);
                }
            });

        return [
            (float) (clone $query)->sum('total'),
            (int) (clone $query)->count(),
        ];
    }

    private function backfillInvoiceCommissions(HrEmployee $employee, Carbon $period): void
    {
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();
        $invoices = Invoice::query()
            ->whereIn('type', [Invoice::TYPE_SELL, Invoice::TYPE_RENT, Invoice::TYPE_TAILORING])
            ->whereNotIn('status', [Invoice::STATUS_CANCELLED, Invoice::STATUS_DRAFT])
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($q) use ($employee): void {
                $q->where('employee_id', $employee->id);
                if ($employee->user_id) {
                    $q->orWhere('created_by', $employee->user_id);
                }
            })
            ->get();

        $commissions = app(EmployeeCommissionService::class);
        foreach ($invoices as $invoice) {
            if (! $invoice->employee_id) {
                $invoice->employee_id = $employee->id;
                $invoice->save();
            }
            $commissions->accrueForInvoice($invoice);
        }
    }

    private function activityTotalForEmployee(HrEmployee $employee, Carbon $period): float
    {
        return $this->activityTotalsForEmployee($employee, $period, 'month')[0];
    }

    /**
     * Yearly statement: base salary × months + commissions from invoices in the year.
     *
     * @return array<string, mixed>
     */
    public function yearlyPayslipForEmployee(HrEmployee $employee, int $year): array
    {
        $employee->loadMissing(['branch', 'department', 'jobTitle']);
        $rules = $this->settingService->all()['payroll_rules'] ?? [];
        $months = [];
        $baseTotal = 0.0;
        $overtimeTotal = 0.0;
        $bonusesTotal = 0.0;
        $commissionsTotal = 0.0;
        $deductionsTotal = 0.0;
        $advancesTotal = 0.0;
        $netTotal = 0.0;

        for ($m = 1; $m <= 12; $m++) {
            $period = Carbon::create($year, $m, 1);
            $row = $this->buildPayrollRow($employee, $period, $rules);
            $months[] = $row;
            $baseTotal += (float) $row['base_salary'];
            $overtimeTotal += (float) $row['overtime'];
            $bonusesTotal += (float) $row['bonuses'];
            $commissionsTotal += (float) $row['commissions'];
            $deductionsTotal += (float) $row['deductions'];
            $advancesTotal += (float) $row['advances'];
            $netTotal += (float) $row['net_salary'];
        }

        $yearPeriod = Carbon::create($year, 1, 1);
        $commissionBreakdown = $this->commissionBreakdown($employee, $yearPeriod, 'year');

        return [
            'period_type' => 'year',
            'year' => $year,
            'month' => (string) $year,
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'employee_code' => $employee->employee_code,
            'branch_name' => $employee->branch?->name ?? '',
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'department' => $employee->department?->name,
                'job_title' => $employee->jobTitle?->name,
                'branch_name' => $employee->branch?->name,
            ],
            'base_salary' => round($baseTotal, 2),
            'attendance_days' => collect($months)->sum('attendance_days'),
            'absent_days' => collect($months)->sum('absent_days'),
            'late_days' => collect($months)->sum('late_days'),
            'overtime' => round($overtimeTotal, 2),
            'advances' => round($advancesTotal, 2),
            'deductions' => round($deductionsTotal, 2),
            'bonuses' => round($bonusesTotal, 2),
            'commissions' => round($commissionsTotal, 2),
            'activity_commissions' => round((float) ($commissionBreakdown['percentage'] ?? 0) + (float) ($commissionBreakdown['fixed'] ?? 0), 2),
            'manual_commissions' => 0,
            'net_salary' => round($netTotal, 2),
            'status' => 'draft',
            'commission_breakdown' => $commissionBreakdown,
            'months' => $months,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function adjustmentLines(int $employeeId, Carbon $period): array
    {
        return HrPayrollAdjustment::query()
            ->where('employee_id', $employeeId)
            ->whereDate('effective_month', $period->format('Y-m').'-01')
            ->orderByDesc('id')
            ->get()
            ->map(fn (HrPayrollAdjustment $row): array => [
                'id' => $row->id,
                'type' => $row->type,
                'amount' => (float) $row->amount,
                'status' => $row->status,
                'notes' => $row->notes,
                'invoice_id' => $row->invoice_id,
            ])
            ->all();
    }
}
