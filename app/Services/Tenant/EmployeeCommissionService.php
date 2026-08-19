<?php

namespace App\Services\Tenant;

use App\Enums\HrCommissionActivity;
use App\Enums\HrCommissionType;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrPayrollAdjustment;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\User;

class EmployeeCommissionService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function attachEmployeeToInvoiceData(array $data, ?int $actorId = null): array
    {
        $locked = $this->employeeIdForActor($actorId);
        if ($locked) {
            $data['employee_id'] = $locked;
        } elseif (empty($data['employee_id']) && $actorId) {
            $fallback = $this->employeeIdForActor($actorId, ignoreOwnerLock: true);
            if ($fallback) {
                $data['employee_id'] = $fallback;
            }
        }

        return $data;
    }

    public function employeeIdForActor(?int $actorId, bool $ignoreOwnerLock = false): ?int
    {
        $user = $this->resolveUser($actorId);
        if (! $user instanceof User) {
            return null;
        }

        if (! $ignoreOwnerLock && $user->isOwner()) {
            return null;
        }

        $employee = $user->relationLoaded('hrEmployee')
            ? $user->hrEmployee
            : HrEmployee::query()->where('user_id', $user->id)->first();

        return $employee?->id ? (int) $employee->id : null;
    }

    public function accrueForInvoice(Invoice $invoice): void
    {
        if (in_array((string) $invoice->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_DRAFT], true)) {
            $this->voidForInvoice($invoice);

            return;
        }

        $employee = $this->employeeForInvoice($invoice);
        if (! $employee instanceof HrEmployee) {
            $this->voidForInvoice($invoice);

            return;
        }

        $amount = $this->commissionAmount($employee, $invoice);
        if ($amount <= 0) {
            $this->voidForInvoice($invoice);

            return;
        }

        $month = ($invoice->created_at ?? now())->copy()->startOfMonth()->toDateString();
        $existing = HrPayrollAdjustment::query()
            ->where('invoice_id', $invoice->id)
            ->where('type', HrPayrollAdjustment::TYPE_COMMISSION)
            ->first();

        $payload = [
            'employee_id' => $employee->id,
            'type' => HrPayrollAdjustment::TYPE_COMMISSION,
            'amount' => round($amount, 2),
            'effective_month' => $month,
            'status' => 'approved',
            'invoice_id' => $invoice->id,
            'notes' => $this->noteFor($invoice, $employee, $amount),
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return;
        }

        HrPayrollAdjustment::query()->create($payload);
    }

    public function voidForInvoice(Invoice $invoice): void
    {
        HrPayrollAdjustment::query()
            ->where('invoice_id', $invoice->id)
            ->where('type', HrPayrollAdjustment::TYPE_COMMISSION)
            ->delete();
    }

    public function employeeForInvoice(Invoice $invoice): ?HrEmployee
    {
        if ($invoice->employee_id) {
            $employee = HrEmployee::query()->find((int) $invoice->employee_id);
            if ($employee instanceof HrEmployee) {
                return $employee;
            }
        }

        if ($invoice->created_by) {
            return HrEmployee::query()->where('user_id', (int) $invoice->created_by)->first();
        }

        return null;
    }

    public function commissionAmount(HrEmployee $employee, Invoice $invoice): float
    {
        $type = (string) ($employee->commission_type ?? HrCommissionType::NONE->value);
        if ($type === HrCommissionType::NONE->value) {
            return 0.0;
        }

        if (! $this->activityMatches($employee, (string) $invoice->type)) {
            return 0.0;
        }

        $total = (float) $invoice->total;
        $fixed = 0.0;
        $percent = 0.0;

        if (in_array($type, [HrCommissionType::FIXED->value, HrCommissionType::MIXED->value], true)) {
            $fixed = (float) ($employee->commission_fixed_amount ?? 0);
        }

        if (in_array($type, [HrCommissionType::PERCENTAGE->value, HrCommissionType::MIXED->value], true)) {
            $rate = (float) ($employee->commission_rate ?? 0);
            if ($rate > 0) {
                $percent = round($total * ($rate / 100), 2);
            }
        }

        return round(max(0, $fixed + $percent), 2);
    }

    public function activityMatches(HrEmployee $employee, string $invoiceType): bool
    {
        $activity = (string) ($employee->commission_activity ?? HrCommissionActivity::ALL->value);
        if ($activity === HrCommissionActivity::ALL->value || $activity === '') {
            return in_array($invoiceType, [Invoice::TYPE_SELL, Invoice::TYPE_RENT, Invoice::TYPE_TAILORING], true);
        }

        $expected = match ($activity) {
            HrCommissionActivity::SALE->value, 'sell' => Invoice::TYPE_SELL,
            HrCommissionActivity::RENT->value => Invoice::TYPE_RENT,
            HrCommissionActivity::TAILORING->value => Invoice::TYPE_TAILORING,
            default => null,
        };

        return $expected !== null && $expected === $invoiceType;
    }

    private function resolveUser(?int $actorId): ?User
    {
        $user = auth()->user();
        if ($user instanceof User) {
            return $user;
        }

        if ($actorId) {
            $found = User::query()->find($actorId);

            return $found instanceof User ? $found : null;
        }

        return null;
    }

    private function noteFor(Invoice $invoice, HrEmployee $employee, float $amount): string
    {
        $kind = match ((string) $invoice->type) {
            Invoice::TYPE_SELL => 'بيع',
            Invoice::TYPE_RENT => 'إيجار',
            Invoice::TYPE_TAILORING => 'تفصيل',
            default => (string) $invoice->type,
        };

        return sprintf(
            'عمولة %s على الفاتورة %s — %.2f',
            $kind,
            (string) $invoice->invoice_number,
            $amount,
        );
    }
}
