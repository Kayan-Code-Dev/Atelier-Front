<?php

namespace App\Services\Tenant;

use App\Accounting\AccountBalanceService;
use App\Accounting\AccountingAuditService;
use App\Accounting\AccountingMoney;
use App\Models\Tenant\Account;
use App\Models\Tenant\BankAccount;
use App\Models\Tenant\BankReconciliation;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class BankAccountService
{
    public function __construct(
        private readonly AccountBalanceService $balances,
        private readonly AccountingAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = BankAccount::query()
            ->with(['account:id,code,name', 'branch:id,name'])
            ->orderBy('bank_name')
            ->orderBy('name');

        $this->applyBranchScope($query, $filters);
        if (($filters['status'] ?? '') !== '' && ($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $wildcard = '%'.mb_strtolower($search).'%';
            $query->where(function (Builder $builder) use ($wildcard): void {
                $builder->whereRaw('LOWER(name) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(bank_name) LIKE ?', [$wildcard])
                    ->orWhere('account_number_last4', 'like', '%'.preg_replace('/\D+/', '', $search).'%');
            });
        }

        $page = $query->paginate($perPage);
        $asOf = (string) ($filters['date_to'] ?? now()->toDateString());
        $page->setCollection(
            $page->getCollection()->map(fn (BankAccount $account) => $this->present($account, $asOf))
        );

        return $page;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId): BankAccount
    {
        $account = $this->requireGlAccount((int) $data['account_id']);
        $numbers = $this->maskNumbers($data);

        $existing = BankAccount::query()
            ->where('account_number_fingerprint', $numbers['account_number_fingerprint'])
            ->where('bank_name', $data['bank_name'])
            ->first();
        if ($existing instanceof BankAccount) {
            throw ValidationException::withMessages(['account_number' => ['هذا الحساب البنكي مسجّل مسبقاً.']]);
        }

        try {
            $bank = BankAccount::query()->create([
                'name' => $data['name'],
                'bank_name' => $data['bank_name'],
                'account_number_last4' => $numbers['account_number_last4'],
                'account_number_fingerprint' => $numbers['account_number_fingerprint'],
                'iban_last4' => $numbers['iban_last4'],
                'iban_fingerprint' => $numbers['iban_fingerprint'],
                'currency' => strtoupper((string) ($data['currency'] ?? 'LYD')),
                'branch_id' => $data['branch_id'] ?? null,
                'account_id' => $account->id,
                'cashbox_id' => $data['cashbox_id'] ?? null,
                'opening_balance' => AccountingMoney::toFloat($data['opening_balance'] ?? 0),
                'status' => $data['status'] ?? BankAccount::STATUS_ACTIVE,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException|QueryException $e) {
            if ($e instanceof QueryException && ! str_contains((string) $e->getMessage(), 'UNIQUE')) {
                throw $e;
            }
            throw ValidationException::withMessages(['account_number' => ['هذا الحساب البنكي مسجّل مسبقاً.']]);
        }

        $this->audit->record($actorId, 'bank_account_created', 'bank_account', $bank->id, [
            'bank_name' => $bank->bank_name,
            'masked' => $bank->maskedAccountNumber(),
        ]);

        return $bank->load(['account:id,code,name', 'branch:id,name']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BankAccount $bank, array $data, ?int $actorId): BankAccount
    {
        if (isset($data['account_id'])) {
            $this->requireGlAccount((int) $data['account_id']);
        }

        $payload = [
            'name' => $data['name'] ?? $bank->name,
            'bank_name' => $data['bank_name'] ?? $bank->bank_name,
            'currency' => strtoupper((string) ($data['currency'] ?? $bank->currency)),
            'branch_id' => array_key_exists('branch_id', $data) ? $data['branch_id'] : $bank->branch_id,
            'account_id' => $data['account_id'] ?? $bank->account_id,
            'cashbox_id' => array_key_exists('cashbox_id', $data) ? $data['cashbox_id'] : $bank->cashbox_id,
            'opening_balance' => AccountingMoney::toFloat($data['opening_balance'] ?? $bank->opening_balance),
            'status' => $data['status'] ?? $bank->status,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $bank->notes,
        ];

        if (! empty($data['account_number'])) {
            $numbers = $this->maskNumbers($data + ['account_number' => $data['account_number']]);
            $payload['account_number_last4'] = $numbers['account_number_last4'];
            $payload['account_number_fingerprint'] = $numbers['account_number_fingerprint'];
        }
        if (array_key_exists('iban', $data)) {
            $numbers = $this->maskNumbers(['iban' => $data['iban'], 'account_number' => '0000']);
            $payload['iban_last4'] = $numbers['iban_last4'];
            $payload['iban_fingerprint'] = $numbers['iban_fingerprint'];
        }

        $bank->update($payload);
        $this->audit->record($actorId, 'bank_account_updated', 'bank_account', $bank->id, [
            'masked' => $bank->maskedAccountNumber(),
        ]);

        return $bank->fresh(['account:id,code,name', 'branch:id,name']) ?? $bank;
    }

    public function findOrFail(int $id, array $filters = []): BankAccount
    {
        $query = BankAccount::query()->with(['account:id,code,name', 'branch:id,name']);
        $this->applyBranchScope($query, $filters);

        return $query->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(BankAccount $bank, ?string $asOf = null): array
    {
        $asOf ??= now()->toDateString();
        $systemBalance = $this->systemBalance($bank, $asOf);
        $last = $bank->reconciliations()
            ->whereIn('status', [BankReconciliation::STATUS_RECONCILED, BankReconciliation::STATUS_LOCKED])
            ->orderByDesc('date_to')
            ->orderByDesc('id')
            ->first();

        return [
            'id' => $bank->id,
            'name' => $bank->name,
            'bank_name' => $bank->bank_name,
            'account_name' => $bank->name,
            'account_number_masked' => $bank->maskedAccountNumber(),
            'iban_masked' => $bank->maskedIban(),
            'currency' => $bank->currency,
            'branch_id' => $bank->branch_id,
            'branch_name' => $bank->branch?->name,
            'account_id' => $bank->account_id,
            'gl_account' => $bank->account ? [
                'id' => $bank->account->id,
                'code' => $bank->account->code,
                'name' => $bank->account->name,
            ] : null,
            'opening_balance' => AccountingMoney::toFloat($bank->opening_balance),
            'system_balance' => $systemBalance,
            'status' => $bank->status,
            'last_reconciliation' => $last?->date_to?->format('d/m/Y'),
            'last_reconciliation_status' => $last?->status,
        ];
    }

    public function systemBalance(BankAccount $bank, string $asOf): float
    {
        $account = $bank->account ?? Account::query()->find($bank->account_id);
        if (! $account instanceof Account) {
            return 0.0;
        }

        return $this->balances->balanceByAccountId($account->id, $asOf, $bank->branch_id ? (int) $bank->branch_id : null);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyBranchScope(Builder $query, array $filters, string $column = 'branch_id'): void
    {
        $branchId = $this->resolveBranchId($filters);
        if ($branchId !== null) {
            $query->where($column, $branchId);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function resolveBranchId(array $filters): ?int
    {
        if (($filters['branch_id'] ?? '') !== '' && $filters['branch_id'] !== null) {
            return (int) $filters['branch_id'];
        }

        $user = $filters['user'] ?? null;
        if ($user instanceof User && ! $user->isOwner() && $user->branch_id) {
            return (int) $user->branch_id;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{account_number_last4: string, account_number_fingerprint: string, iban_last4: string|null, iban_fingerprint: string|null}
     */
    public function maskNumbers(array $data): array
    {
        $number = preg_replace('/\s+/', '', (string) ($data['account_number'] ?? '')) ?? '';
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        if (strlen($digits) < 4) {
            throw ValidationException::withMessages(['account_number' => ['رقم الحساب يجب أن يحتوي على 4 أرقام على الأقل.']]);
        }

        $iban = strtoupper(preg_replace('/\s+/', '', (string) ($data['iban'] ?? '')) ?? '');
        $ibanDigits = $iban !== '' ? substr(preg_replace('/[^A-Z0-9]/', '', $iban) ?? '', -4) : null;

        return [
            'account_number_last4' => substr($digits, -4),
            'account_number_fingerprint' => hash('sha256', $digits),
            'iban_last4' => $ibanDigits,
            'iban_fingerprint' => $iban !== '' ? hash('sha256', $iban) : null,
        ];
    }

    private function requireGlAccount(int $accountId): Account
    {
        $account = Account::query()->find($accountId);
        if (! $account instanceof Account || ! $account->is_active || $account->allow_posting === false) {
            throw ValidationException::withMessages(['account_id' => ['الحساب المحاسبي المرتبط غير صالح للترحيل.']]);
        }

        return $account;
    }
}
