<?php

namespace App\Accounting;

use App\Models\Tenant\Account;
use App\Models\Tenant\AccountingPeriod;
use App\Models\Tenant\Branch;
use Illuminate\Validation\ValidationException;

class JournalEntryValidator
{
    /**
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, mixed>  $context
     */
    public function validate(array $lines, array $context = []): void
    {
        $requireBalanced = (bool) ($context['require_balanced'] ?? false);
        $entryDate = $context['entry_date'] ?? null;
        $branchId = $context['branch_id'] ?? null;

        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => ['At least one journal line is required.']]);
        }

        $accounts = Account::query()
            ->whereIn('id', array_values(array_unique(array_filter(array_map(
                fn (array $line): int => (int) ($line['account_id'] ?? 0),
                $lines
            )))))
            ->get()
            ->keyBy('id');

        foreach ($lines as $index => $line) {
            $accountId = (int) ($line['account_id'] ?? 0);
            if ($accountId <= 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => ['Account is required.'],
                ]);
            }

            $account = $accounts->get($accountId);
            if (! $account instanceof Account) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => ['Account is invalid.'],
                ]);
            }

            if (! $account->is_active) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => ['Account is inactive.'],
                ]);
            }

            if ($account->allow_posting === false) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => ['Parent accounts cannot be posted to.'],
                ]);
            }

            $debit = AccountingMoney::of($line['debit'] ?? 0);
            $credit = AccountingMoney::of($line['credit'] ?? 0);

            if (AccountingMoney::isPositive($debit) && AccountingMoney::isPositive($credit)) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => ['Each line must contain either debit or credit, not both.'],
                ]);
            }

            if (AccountingMoney::isZero($debit) && AccountingMoney::isZero($credit)) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => ['Each line must contain a debit or credit amount.'],
                ]);
            }
        }

        $totals = $this->totals($lines);

        if ($requireBalanced) {
            if (AccountingMoney::cmp($totals['total_debit'], $totals['total_credit']) !== 0) {
                throw ValidationException::withMessages([
                    'is_balanced' => ['Debit and credit totals must be equal.'],
                ]);
            }

            if (! AccountingMoney::isPositive($totals['total_debit'])) {
                throw ValidationException::withMessages([
                    'total_debit' => ['Posted journal totals must be greater than zero.'],
                ]);
            }
        }

        if ($entryDate) {
            $this->assertPeriodOpen((string) $entryDate);
        }

        if ($branchId) {
            if (! Branch::query()->whereKey((int) $branchId)->exists()) {
                throw ValidationException::withMessages([
                    'branch_id' => ['Branch is invalid for this tenant.'],
                ]);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{total_debit: string, total_credit: string, difference: string, is_balanced: bool}
     */
    public function totals(array $lines): array
    {
        $totalDebit = AccountingMoney::zero();
        $totalCredit = AccountingMoney::zero();

        foreach ($lines as $line) {
            $totalDebit = AccountingMoney::add($totalDebit, $line['debit'] ?? 0);
            $totalCredit = AccountingMoney::add($totalCredit, $line['credit'] ?? 0);
        }

        $difference = AccountingMoney::sub($totalDebit, $totalCredit);

        return [
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'difference' => $difference,
            'is_balanced' => AccountingMoney::isZero($difference),
        ];
    }

    public function assertPeriodOpen(string $date): void
    {
        if (! SchemaHasAccountingPeriods::exists()) {
            return;
        }

        $period = AccountingPeriod::query()
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->first();

        $blocked = $period instanceof AccountingPeriod && (
            $period->is_closed
            || in_array((string) $period->status, [AccountingPeriod::STATUS_CLOSED, AccountingPeriod::STATUS_LOCKED], true)
        );

        if ($blocked) {
            $name = $period->name ?: $date;
            throw ValidationException::withMessages([
                'entry_date' => [
                    'لا يمكن إنشاء أو تعديل أو ترحيل قيد في فترة محاسبية مغلقة ('.$name.'). أنشئ قيد تصحيح في الفترة المفتوحة الحالية.',
                ],
            ]);
        }
    }
}
