<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingPostingService;
use App\Models\Tenant\Account;
use App\Models\Tenant\Cashbox;
use App\Models\Tenant\CashMovement;
use App\Models\Tenant\JournalEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TreasuryOperationService
{
    public const REFERENCE_TREASURY = 'treasury_operation';

    public const REFERENCE_TRANSFER = 'cash_transfer';

    public function __construct(
        private readonly CashMovementService $cashMovements,
        private readonly AccountingPostingService $posting,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{movement: CashMovement, journal: JournalEntry}
     */
    public function receive(array $data, ?int $actorId): array
    {
        return $this->postCashOperation('in', $data, $actorId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{movement: CashMovement, journal: JournalEntry}
     */
    public function pay(array $data, ?int $actorId): array
    {
        return $this->postCashOperation('out', $data, $actorId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{from_movement: CashMovement, to_movement: CashMovement, journal: JournalEntry}
     */
    public function transfer(array $data, ?int $actorId): array
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => ['Amount must be greater than zero.']]);
        }

        $from = Cashbox::query()->findOrFail((int) $data['from_cashbox_id']);
        $to = Cashbox::query()->findOrFail((int) $data['to_cashbox_id']);
        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['to_cashbox_id' => ['Cannot transfer to the same cash account.']]);
        }

        $fromAccount = $this->glAccount($from);
        $toAccount = $this->glAccount($to);
        $date = $data['movement_date'] ?? now()->toDateString();
        $description = $data['description'] ?? ('تحويل من '.$from->name.' إلى '.$to->name);

        return DB::connection('tenant')->transaction(function () use ($from, $to, $fromAccount, $toAccount, $amount, $date, $description, $data, $actorId): array {
            $out = $this->cashMovements->createManual([
                'type' => CashMovement::TYPE_TRANSFER,
                'direction' => CashMovement::DIRECTION_OUT,
                'amount' => $amount,
                'cashbox_id' => $from->id,
                'counterparty_cashbox_id' => $to->id,
                'reference_type' => self::REFERENCE_TRANSFER,
                'reference' => $data['reference'] ?? null,
                'movement_date' => $date,
                'description' => $description,
                'notes' => $data['notes'] ?? null,
            ], $actorId);

            $in = $this->cashMovements->createManual([
                'type' => CashMovement::TYPE_TRANSFER,
                'direction' => CashMovement::DIRECTION_IN,
                'amount' => $amount,
                'cashbox_id' => $to->id,
                'counterparty_cashbox_id' => $from->id,
                'reference_type' => self::REFERENCE_TRANSFER,
                'reference_id' => $out->id,
                'reference' => $data['reference'] ?? null,
                'movement_date' => $date,
                'description' => $description,
                'notes' => $data['notes'] ?? null,
            ], $actorId);

            $journal = $this->posting->postFromSource([
                'entry_date' => Carbon::parse($date)->toDateString(),
                'source_type' => JournalEntry::SOURCE_TREASURY,
                'source_id' => $out->id,
                'source_reference' => $data['reference'] ?? ('TR-'.$out->id),
                'reference_number' => $data['reference'] ?? ('TR-'.$out->id),
                'description' => $description,
                'branch_id' => $from->branch_id ?? $to->branch_id,
            ], [
                ['account_id' => $toAccount->id, 'debit' => $amount, 'credit' => 0, 'description' => 'تحويل وارد — '.$to->name],
                ['account_id' => $fromAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'تحويل صادر — '.$from->name],
            ], $actorId);

            $out->update(['journal_entry_id' => $journal->id, 'reference_id' => $in->id]);
            $in->update(['journal_entry_id' => $journal->id]);

            return [
                'from_movement' => $out->fresh(),
                'to_movement' => $in->fresh(),
                'journal' => $journal,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{movement: CashMovement, journal: JournalEntry}
     */
    private function postCashOperation(string $direction, array $data, ?int $actorId): array
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => ['Amount must be greater than zero.']]);
        }

        $cashbox = Cashbox::query()->findOrFail((int) $data['cashbox_id']);
        $cashAccount = $this->glAccount($cashbox);
        $contra = Account::query()->findOrFail((int) $data['contra_account_id']);
        if (! $contra->allowsPosting()) {
            throw ValidationException::withMessages(['contra_account_id' => ['Contra account cannot be posted to.']]);
        }

        $isIn = $direction === 'in';
        $date = $data['movement_date'] ?? now()->toDateString();
        $description = $data['description'] ?? ($isIn ? 'قبض نقدي' : 'صرف نقدي');

        return DB::connection('tenant')->transaction(function () use ($cashbox, $cashAccount, $contra, $amount, $isIn, $date, $description, $data, $actorId): array {
            $movement = $this->cashMovements->createManual([
                'type' => $isIn ? CashMovement::TYPE_INCOME : CashMovement::TYPE_EXPENSE,
                'direction' => $isIn ? CashMovement::DIRECTION_IN : CashMovement::DIRECTION_OUT,
                'amount' => $amount,
                'method' => $data['method'] ?? null,
                'cashbox_id' => $cashbox->id,
                'contra_account_id' => $contra->id,
                'reference_type' => self::REFERENCE_TREASURY,
                'reference' => $data['reference'] ?? null,
                'movement_date' => $date,
                'description' => $description,
                'notes' => $data['notes'] ?? null,
            ], $actorId);

            $lines = $isIn
                ? [
                    ['account_id' => $cashAccount->id, 'debit' => $amount, 'credit' => 0, 'description' => 'قبض — '.$cashbox->name],
                    ['account_id' => $contra->id, 'debit' => 0, 'credit' => $amount, 'description' => $description],
                ]
                : [
                    ['account_id' => $contra->id, 'debit' => $amount, 'credit' => 0, 'description' => $description],
                    ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'صرف — '.$cashbox->name],
                ];

            $journal = $this->posting->postFromSource([
                'entry_date' => Carbon::parse($date)->toDateString(),
                'source_type' => JournalEntry::SOURCE_TREASURY,
                'source_id' => $movement->id,
                'source_reference' => $data['reference'] ?? $movement->id,
                'reference_number' => $data['reference'] ?? ('CM-'.$movement->id),
                'description' => $description,
                'branch_id' => $cashbox->branch_id,
            ], $lines, $actorId);

            $movement->update(['journal_entry_id' => $journal->id]);

            return [
                'movement' => $movement->fresh(),
                'journal' => $journal,
            ];
        });
    }

    private function glAccount(Cashbox $cashbox): Account
    {
        if ($cashbox->account_id) {
            $account = Account::query()->find($cashbox->account_id);
            if ($account instanceof Account && $account->allowsPosting()) {
                return $account;
            }
        }

        $code = match ($cashbox->kind ?? 'cash') {
            'bank', 'wallet' => '1010',
            default => '1000',
        };
        $account = Account::query()->where('code', $code)->first();
        if (! $account instanceof Account) {
            throw ValidationException::withMessages(['cashbox_id' => ['Cash account is not linked to the general ledger.']]);
        }

        return $account;
    }
}
