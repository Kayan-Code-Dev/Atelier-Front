<?php

namespace App\Services\Tenant;

use App\Enums\HrEmployeeStatus;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrLeaveRequest;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class HrMetricsService
{
    public function __construct(private readonly EmployeeActivityLogger $activityLogger) {}

    /**
     * @return array{present:int,absent:int,late:int,leave:int,day_off:int}
     */
    public function attendanceSnapshotForDate(string $date, ?int $branchId = null): array
    {
        $query = HrAttendanceRecord::query()->whereDate('date', $date);
        $this->applyBranchFilter($query, $branchId);

        return [
            'present' => (clone $query)->where('status', 'present')->count(),
            'absent' => (clone $query)->where('status', 'absent')->count(),
            'late' => (clone $query)->where('status', 'late')->count(),
            'leave' => (clone $query)->where('status', 'leave')->count(),
            'day_off' => (clone $query)->where('status', 'day_off')->count(),
        ];
    }

    public function countOnLeaveToday(?int $branchId = null): int
    {
        $today = Carbon::today()->toDateString();
        $query = HrLeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today);

        $this->applyBranchFilterOnLeave($query, $branchId);

        return $query->count();
    }

    public function countPendingLeaveRequests(?int $branchId = null): int
    {
        $query = HrLeaveRequest::query()->where('status', 'pending');
        $this->applyBranchFilterOnLeave($query, $branchId);

        return $query->count();
    }

    /**
     * @return array{gross_salaries:float,deductions:float,bonuses:float,net_payroll:float}
     */
    public function payrollEstimate(?int $branchId = null): array
    {
        $payroll = app(HrPayrollService::class)->payrollForMonth(now()->format('Y-m'), $branchId);
        $summary = $payroll['summary'] ?? [];

        return [
            'gross_salaries' => (float) ($summary['gross'] ?? 0),
            'deductions' => (float) ($summary['deductions'] ?? 0),
            'bonuses' => (float) ($summary['bonuses'] ?? 0),
            'commissions' => (float) ($summary['commissions'] ?? 0),
            'net_payroll' => (float) ($summary['net'] ?? 0),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function upcomingEvents(?int $branchId = null, int $limit = 8): array
    {
        $today = Carbon::today();
        $until = $today->copy()->addDays(14)->toDateString();

        $leaves = HrLeaveRequest::query()
            ->with('employee:id,full_name,employee_code')
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('from_date', '>=', $today->toDateString())
            ->whereDate('from_date', '<=', $until)
            ->when($branchId !== null, fn (Builder $query) => $this->applyBranchFilterOnLeave($query, $branchId))
            ->orderBy('from_date')
            ->limit($limit)
            ->get();

        return $leaves->map(static function (HrLeaveRequest $leave): array {
            return [
                'id' => $leave->id,
                'type' => 'leave',
                'employee_name' => $leave->employee?->full_name ?? 'موظف',
                'label' => 'إجازة — '.$leave->type.' ('.$leave->status.')',
                'date' => $leave->from_date?->toDateString(),
                'status' => $leave->status,
                'employee_id' => $leave->employee_id,
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentActivity(?int $branchId = null, int $limit = 25): array
    {
        try {
            $fromLogs = $this->activityLogger->recentForDashboard($branchId, $limit);
            if ($fromLogs !== []) {
                return $fromLogs;
            }
        } catch (\Throwable) {
            // fall through to legacy snapshot
        }

        $items = [];

        $attendance = HrAttendanceRecord::query()
            ->with('employee:id,full_name')
            ->when($branchId !== null, fn (Builder $query) => $this->applyBranchFilter($query, $branchId))
            ->latest('updated_at')
            ->limit($limit)
            ->get();

        foreach ($attendance as $record) {
            $items[] = [
                'id' => (int) ('1'.str_pad((string) $record->id, 8, '0', STR_PAD_LEFT)),
                'type' => 'attendance',
                'title' => 'حضور — '.($record->employee?->full_name ?? 'موظف'),
                'description' => 'حالة الحضور: '.$record->status,
                'actor' => $record->employee?->full_name,
                'date' => $record->date?->toDateString(),
                'at' => $record->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        $leaves = HrLeaveRequest::query()
            ->with('employee:id,full_name')
            ->when($branchId !== null, fn (Builder $query) => $this->applyBranchFilterOnLeave($query, $branchId))
            ->latest('updated_at')
            ->limit($limit)
            ->get();

        foreach ($leaves as $leave) {
            $items[] = [
                'id' => (int) ('2'.str_pad((string) $leave->id, 8, '0', STR_PAD_LEFT)),
                'type' => 'leave',
                'title' => 'إجازة — '.($leave->employee?->full_name ?? 'موظف'),
                'description' => 'حالة الطلب: '.$leave->status,
                'actor' => $leave->employee?->full_name,
                'date' => $leave->from_date?->toDateString(),
                'at' => $leave->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        usort($items, static fn (array $a, array $b) => strcmp((string) ($b['at'] ?? ''), (string) ($a['at'] ?? '')));

        return array_slice($items, 0, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function employeeMonthAttendance(HrEmployee $employee, ?CarbonInterface $month = null): array
    {
        $start = ($month ?? Carbon::now())->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $query = $employee->attendanceRecords()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);

        return [
            'present_days_this_month' => (clone $query)->whereIn('status', ['present', 'late', 'half_day'])->count(),
            'late_days_this_month' => (clone $query)->where('status', 'late')->count(),
            'absent_days_this_month' => (clone $query)->where('status', 'absent')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function employeeMonthLeaves(HrEmployee $employee, ?CarbonInterface $month = null): array
    {
        $start = ($month ?? Carbon::now())->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $approvedDays = (float) $employee->leaveRequests()
            ->where('status', 'approved')
            ->where(function (Builder $query) use ($start, $end): void {
                $query->whereBetween('from_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('to_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->sum('days');

        return [
            'approved_leaves_this_month' => $approvedDays,
            'pending_requests' => $employee->leaveRequests()->where('status', 'pending')->count(),
            'leave_balances' => [],
        ];
    }

    /**
     * @param  Builder<HrAttendanceRecord>  $query
     */
    private function applyBranchFilter(Builder $query, ?int $branchId): void
    {
        if ($branchId === null) {
            return;
        }

        $query->whereHas('employee', fn (Builder $builder) => $builder->where('branch_id', $branchId));
    }

    /**
     * @param  Builder<HrLeaveRequest>  $query
     */
    private function applyBranchFilterOnLeave(Builder $query, ?int $branchId): Builder
    {
        if ($branchId !== null) {
            $query->whereHas('employee', fn (Builder $builder) => $builder->where('branch_id', $branchId));
        }

        return $query;
    }
}
