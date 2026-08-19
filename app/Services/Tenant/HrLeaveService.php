<?php

namespace App\Services\Tenant;

use App\Models\Tenant\HrLeaveRequest;
use App\Models\Tenant\HrPayrollAdjustment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HrLeaveService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = HrLeaveRequest::query()
            ->with(['employee', 'reviewer'])
            ->latest('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): HrLeaveRequest
    {
        if (! isset($data['days'])) {
            $data['days'] = $this->calculateDays($data['from_date'], $data['to_date']);
        }

        $type = (string) ($data['type'] ?? 'annual');
        if (! array_key_exists('is_paid', $data) || $data['is_paid'] === null) {
            $data['is_paid'] = $type !== 'unpaid';
        }
        $data['is_paid'] = filter_var($data['is_paid'], FILTER_VALIDATE_BOOLEAN);
        if (! $data['is_paid']) {
            $data['deduction_amount'] = $data['deduction_amount'] ?? null;
        } else {
            $data['deduction_amount'] = isset($data['deduction_amount']) ? (float) $data['deduction_amount'] : 0;
        }

        return HrLeaveRequest::query()->create($data)->load(['employee', 'reviewer']);
    }

    public function findOrFail(int $id): HrLeaveRequest
    {
        return HrLeaveRequest::query()->with(['employee', 'reviewer'])->findOrFail($id);
    }

    public function updateStatus(HrLeaveRequest $leaveRequest, array $data, int $reviewerId): HrLeaveRequest
    {
        $leaveRequest->status = $data['status'];
        $leaveRequest->review_notes = $data['review_notes'] ?? null;
        $leaveRequest->reviewed_by = $reviewerId;
        $leaveRequest->reviewed_at = now();
        $leaveRequest->save();

        if ($leaveRequest->status === 'approved') {
            $this->syncLeaveDeduction($leaveRequest);
        }

        return $leaveRequest->refresh()->load(['employee', 'reviewer']);
    }

    private function syncLeaveDeduction(HrLeaveRequest $leaveRequest): void
    {
        $amount = (float) ($leaveRequest->deduction_amount ?? 0);
        if ($amount <= 0) {
            return;
        }

        $month = Carbon::parse($leaveRequest->from_date)->startOfMonth()->toDateString();
        $existing = HrPayrollAdjustment::query()
            ->where('leave_request_id', $leaveRequest->id)
            ->first();

        $payload = [
            'employee_id' => $leaveRequest->employee_id,
            'type' => HrPayrollAdjustment::TYPE_DEDUCTION,
            'amount' => $amount,
            'effective_month' => $month,
            'status' => 'approved',
            'leave_request_id' => $leaveRequest->id,
            'notes' => 'خصم إجازة #'.$leaveRequest->id.($leaveRequest->is_paid ? ' (مدفوعة)' : ' (غير مدفوعة)'),
        ];

        if ($existing) {
            $existing->fill($payload)->save();

            return;
        }

        HrPayrollAdjustment::query()->create($payload);
    }

    private function calculateDays(string $fromDate, string $toDate): float
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();

        return (float) ($from->diffInDays($to) + 1);
    }
}
