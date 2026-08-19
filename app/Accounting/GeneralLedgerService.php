<?php

namespace App\Accounting;

use App\Models\Tenant\Account;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\JournalEntryLine;
use App\Support\ReportDateRange;
use Illuminate\Database\Eloquent\Builder;

class GeneralLedgerService
{
    public function __construct(private readonly AccountBalanceService $balances) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{account: array<string, mixed>, opening_balance: float, closing_balance: float, debit_total: float, credit_total: float, lines: list<array<string, mixed>>}
     */
    public function statement(int $accountId, array $filters): array
    {
        $account = Account::query()->findOrFail($accountId);
        $period = ReportDateRange::resolve($filters);
        $branchId = isset($filters['branch_id']) && $filters['branch_id'] !== '' ? (int) $filters['branch_id'] : null;
        $search = trim((string) ($filters['search'] ?? ''));
        $sourceType = trim((string) ($filters['source_type'] ?? ''));
        $reference = trim((string) ($filters['reference'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $opening = $this->balances->openingBalance($accountId, $period['from'], $branchId);
        $running = $opening;
        $debitTotal = AccountingMoney::zero();
        $creditTotal = AccountingMoney::zero();

        $query = JournalEntryLine::query()
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function (Builder $builder) use ($period, $branchId, $sourceType, $status, $reference): void {
                $builder->whereIn('status', $this->statusFilter($status))
                    ->whereDate('entry_date', '>=', $period['from'])
                    ->whereDate('entry_date', '<=', $period['to']);
                if ($branchId) {
                    $builder->where('branch_id', $branchId);
                }
                if ($sourceType !== '' && $sourceType !== 'all') {
                    $builder->where('source_type', $sourceType);
                }
                if ($reference !== '') {
                    $builder->where(function (Builder $query) use ($reference): void {
                        $query->where('entry_number', 'like', '%'.$reference.'%')
                            ->orWhere('reference_number', 'like', '%'.$reference.'%')
                            ->orWhere('source_reference', 'like', '%'.$reference.'%');
                    });
                }
            })
            ->with(['journalEntry:id,entry_number,entry_date,description,reference_number,source_reference,source_type,source_id,status'])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*');

        $lines = [];
        if (! AccountingMoney::isZero($opening)) {
            $lines[] = [
                'id' => 0,
                'date' => $period['from'],
                'type' => AccountingMoney::cmp($opening, '0') >= 0 ? ($account->isDebitNormal() ? 'debit' : 'credit') : ($account->isDebitNormal() ? 'credit' : 'debit'),
                'reference' => 'OPENING',
                'journal_number' => 'OPENING',
                'source' => null,
                'description' => 'رصيد افتتاحي',
                'debit' => 0,
                'credit' => 0,
                'balance' => AccountingMoney::toFloat($opening),
            ];
        }

        foreach ($query->get() as $line) {
            $journal = $line->journalEntry;
            $hay = mb_strtolower(trim(
                ($journal?->entry_number ?? '').' '.
                ($journal?->reference_number ?? '').' '.
                ($line->description ?? '').' '.
                ($journal?->description ?? '')
            ));
            if ($search !== '' && ! str_contains($hay, mb_strtolower($search))) {
                continue;
            }

            $debit = AccountingMoney::of($line->debit);
            $credit = AccountingMoney::of($line->credit);
            $debitTotal = AccountingMoney::add($debitTotal, $debit);
            $creditTotal = AccountingMoney::add($creditTotal, $credit);
            $delta = $account->isDebitNormal()
                ? AccountingMoney::sub($debit, $credit)
                : AccountingMoney::sub($credit, $debit);
            $running = AccountingMoney::add($running, $delta);

            $lines[] = [
                'id' => (int) $line->id,
                'date' => $journal?->entry_date?->toDateString() ?? '',
                'type' => AccountingMoney::isPositive($debit) ? 'debit' : 'credit',
                'reference' => $journal?->entry_number ?? $journal?->reference_number ?? (string) $line->id,
                'journal_number' => $journal?->entry_number,
                'journal_id' => $journal?->id,
                'source' => $journal?->source_type,
                'source_id' => $journal?->source_id,
                'source_url' => $journal ? JournalSourcePresenter::url((string) ($journal->source_type ?: ''), $journal->source_id ? (int) $journal->source_id : null) : null,
                'source_label' => $journal ? JournalSourcePresenter::label((string) ($journal->source_type ?: ''), $journal->source_reference ?: $journal->reference_number, $journal->source_id ? (int) $journal->source_id : null) : null,
                'description' => $line->description ?: ($journal?->description ?? ''),
                'debit' => AccountingMoney::toFloat($debit),
                'credit' => AccountingMoney::toFloat($credit),
                'balance' => AccountingMoney::toFloat($running),
            ];
        }

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
            ],
            'opening_balance' => AccountingMoney::toFloat($opening),
            'closing_balance' => AccountingMoney::toFloat($running),
            'debit_total' => AccountingMoney::toFloat($debitTotal),
            'credit_total' => AccountingMoney::toFloat($creditTotal),
            'lines' => $lines,
        ];
    }

    /**
     * @return list<string>
     */
    private function statusFilter(string $status): array
    {
        if ($status === '' || $status === 'all' || $status === 'posted' || $status === 'approved') {
            return JournalEntry::postedStatuses();
        }

        return [$status];
    }
}
