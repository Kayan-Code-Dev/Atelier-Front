<?php

namespace App\Accounting;

use App\Models\Tenant\Account;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\JournalEntryLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AccountBalanceService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function balancesByAccount(?string $from, string $to, ?int $branchId = null): Collection
    {
        $totals = JournalEntryLine::query()
            ->selectRaw('account_id, SUM(debit) as debit_sum, SUM(credit) as credit_sum')
            ->whereHas('journalEntry', function (Builder $query) use ($from, $to, $branchId): void {
                $this->constrainPosted($query, $from, $to, $branchId, beforeFrom: false);
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        return Account::query()
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($totals): array {
                $row = $totals->get($account->id);
                $debit = AccountingMoney::of($row->debit_sum ?? 0);
                $credit = AccountingMoney::of($row->credit_sum ?? 0);

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'parent_id' => $account->parent_id,
                    'is_active' => (bool) $account->is_active,
                    'allow_posting' => $account->allow_posting !== false,
                    'debit' => AccountingMoney::toFloat($debit),
                    'credit' => AccountingMoney::toFloat($credit),
                    'current_balance' => AccountingMoney::toFloat($this->signed($account, $debit, $credit)),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function trialBalance(?string $from, string $to, ?int $branchId = null, ?int $accountId = null, ?string $type = null, ?string $sourceType = null): Collection
    {
        $periodFrom = $from ?: '1970-01-01';
        $openingRows = JournalEntryLine::query()
            ->selectRaw('account_id, SUM(debit) as debit_sum, SUM(credit) as credit_sum')
            ->whereHas('journalEntry', function (Builder $query) use ($periodFrom, $branchId, $sourceType): void {
                $this->constrainPosted($query, null, $periodFrom, $branchId, beforeFrom: true);
                if ($sourceType) {
                    $query->where('source_type', $sourceType);
                }
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $periodRows = JournalEntryLine::query()
            ->selectRaw('account_id, SUM(debit) as debit_sum, SUM(credit) as credit_sum')
            ->whereHas('journalEntry', function (Builder $query) use ($from, $to, $branchId, $sourceType): void {
                $this->constrainPosted($query, $from, $to, $branchId, beforeFrom: false);
                if ($sourceType) {
                    $query->where('source_type', $sourceType);
                }
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        return Account::query()
            ->when($accountId, fn (Builder $query) => $query->where('id', $accountId))
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->where('allow_posting', true)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($openingRows, $periodRows): array {
                $openingDebit = AccountingMoney::of($openingRows->get($account->id)?->debit_sum ?? 0);
                $openingCredit = AccountingMoney::of($openingRows->get($account->id)?->credit_sum ?? 0);
                $periodDebit = AccountingMoney::of($periodRows->get($account->id)?->debit_sum ?? 0);
                $periodCredit = AccountingMoney::of($periodRows->get($account->id)?->credit_sum ?? 0);
                $opening = $this->signed($account, $openingDebit, $openingCredit);
                $periodSigned = $this->signed($account, $periodDebit, $periodCredit);
                $closing = AccountingMoney::add($opening, $periodSigned);

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'opening_balance' => AccountingMoney::toFloat($opening),
                    'debit' => AccountingMoney::toFloat($periodDebit),
                    'credit' => AccountingMoney::toFloat($periodCredit),
                    'closing_balance' => AccountingMoney::toFloat($closing),
                ];
            })
            ->values();
    }

    public function balanceByCode(string $code, string $asOf, ?int $branchId = null): float
    {
        $account = Account::query()->where('code', $code)->first();
        if (! $account instanceof Account) {
            return 0.0;
        }

        $debit = AccountingMoney::of($this->approvedLinesQuery($account->id, null, $asOf, $branchId, false)->sum('debit'));
        $credit = AccountingMoney::of($this->approvedLinesQuery($account->id, null, $asOf, $branchId, false)->sum('credit'));

        return AccountingMoney::toFloat($this->signed($account, $debit, $credit));
    }

    public function balanceByAccountId(int $accountId, string $asOf, ?int $branchId = null): float
    {
        $account = Account::query()->find($accountId);
        if (! $account instanceof Account) {
            return 0.0;
        }

        $debit = AccountingMoney::of($this->approvedLinesQuery($account->id, null, $asOf, $branchId, false)->sum('debit'));
        $credit = AccountingMoney::of($this->approvedLinesQuery($account->id, null, $asOf, $branchId, false)->sum('credit'));

        return AccountingMoney::toFloat($this->signed($account, $debit, $credit));
    }

    /**
     * @param  list<string>  $codes
     */
    public function sumCodes(array $codes, string $asOf, ?int $branchId = null): float
    {
        $total = AccountingMoney::zero();
        foreach ($codes as $code) {
            $total = AccountingMoney::add($total, $this->balanceByCode($code, $asOf, $branchId));
        }

        return AccountingMoney::toFloat($total);
    }

    /**
     * @return array{debit: float, credit: float}
     */
    public function periodMovementByCodes(array $codes, string $from, string $to, ?int $branchId = null): array
    {
        $accountIds = Account::query()->whereIn('code', $codes)->pluck('id');
        $row = JournalEntryLine::query()
            ->selectRaw('COALESCE(SUM(debit),0) as debit_sum, COALESCE(SUM(credit),0) as credit_sum')
            ->whereIn('account_id', $accountIds)
            ->whereHas('journalEntry', function (Builder $query) use ($from, $to, $branchId): void {
                $this->constrainPosted($query, $from, $to, $branchId, beforeFrom: false);
            })
            ->first();

        return [
            'debit' => AccountingMoney::toFloat(AccountingMoney::of($row->debit_sum ?? 0)),
            'credit' => AccountingMoney::toFloat(AccountingMoney::of($row->credit_sum ?? 0)),
        ];
    }

    public function openingBalance(int $accountId, string $from, ?int $branchId = null): string
    {
        $account = Account::query()->findOrFail($accountId);
        $debit = AccountingMoney::of(
            $this->approvedLinesQuery($accountId, null, $from, $branchId, true)->sum('debit')
        );
        $credit = AccountingMoney::of(
            $this->approvedLinesQuery($accountId, null, $from, $branchId, true)->sum('credit')
        );

        return $this->signed($account, $debit, $credit);
    }

    /**
     * @return array{debit: string, credit: string, balance: string}
     */
    public function periodTotals(int $accountId, string $from, string $to, ?int $branchId = null): array
    {
        $account = Account::query()->findOrFail($accountId);
        $debit = AccountingMoney::of(
            $this->approvedLinesQuery($accountId, $from, $to, $branchId, false)->sum('debit')
        );
        $credit = AccountingMoney::of(
            $this->approvedLinesQuery($accountId, $from, $to, $branchId, false)->sum('credit')
        );

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $this->signed($account, $debit, $credit),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $accounts
     */
    public function sumType(Collection $accounts, string $type): float
    {
        $total = AccountingMoney::zero();
        foreach ($accounts->where('type', $type) as $account) {
            $total = AccountingMoney::add($total, $account['current_balance']);
        }

        return AccountingMoney::toFloat($total);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $accounts
     */
    public function retainedEarnings(Collection $accounts): float
    {
        return AccountingMoney::toFloat(AccountingMoney::sub(
            $this->sumType($accounts, Account::TYPE_REVENUE),
            $this->sumType($accounts, Account::TYPE_EXPENSE),
        ));
    }

    public function signed(Account $account, mixed $debit, mixed $credit): string
    {
        return $account->isDebitNormal()
            ? AccountingMoney::sub($debit, $credit)
            : AccountingMoney::sub($credit, $debit);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $accounts
     * @return array{items: list<array<string, mixed>>, total: float}
     */
    public function reportSection(Collection $accounts, string $type): array
    {
        $items = $accounts
            ->where('type', $type)
            ->filter(fn (array $account): bool => abs((float) $account['current_balance']) >= 0.005)
            ->filter(fn (array $account): bool => ($account['allow_posting'] ?? true) === true)
            ->values()
            ->map(fn (array $account): array => [
                'id' => $account['id'],
                'code' => $account['code'],
                'name' => $account['name'],
                'current_balance' => $account['current_balance'],
            ])
            ->all();

        $total = AccountingMoney::zero();
        foreach ($items as $item) {
            $total = AccountingMoney::add($total, $item['current_balance']);
        }

        return [
            'items' => $items,
            'total' => AccountingMoney::toFloat($total),
        ];
    }

    private function approvedLinesQuery(
        int $accountId,
        ?string $from,
        string $to,
        ?int $branchId,
        bool $beforeFrom
    ): Builder {
        return JournalEntryLine::query()
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function (Builder $query) use ($from, $to, $branchId, $beforeFrom): void {
                $this->constrainPosted($query, $from, $to, $branchId, $beforeFrom);
            });
    }

    private function constrainPosted(Builder $query, ?string $from, string $to, ?int $branchId, bool $beforeFrom): void
    {
        $query->whereIn('status', JournalEntry::postedStatuses());
        if ($beforeFrom) {
            $query->whereDate('entry_date', '<', $to);
        } else {
            $query->whereDate('entry_date', '<=', $to);
            if ($from) {
                $query->whereDate('entry_date', '>=', $from);
            }
        }
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
    }
}
