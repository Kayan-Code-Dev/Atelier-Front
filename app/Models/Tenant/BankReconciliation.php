<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends BaseTenantModel
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEW = 'review';

    public const STATUS_RECONCILED = 'reconciled';

    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'bank_account_id',
        'branch_id',
        'date_from',
        'date_to',
        'statement_balance',
        'ledger_balance',
        'deposits_in_transit',
        'outstanding_payments',
        'adjusted_bank_balance',
        'difference',
        'status',
        'notes',
        'created_by',
        'locked_by',
        'reopened_by',
        'submitted_at',
        'reconciled_at',
        'locked_at',
        'reopened_at',
        'reopen_reason',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'statement_balance' => 'decimal:2',
            'ledger_balance' => 'decimal:2',
            'deposits_in_transit' => 'decimal:2',
            'outstanding_payments' => 'decimal:2',
            'adjusted_bank_balance' => 'decimal:2',
            'difference' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'locked_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BankReconciliationMatch::class, 'reconciliation_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(BankReconciliationAdjustment::class, 'reconciliation_id');
    }

    public function statementImports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class, 'reconciliation_id');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REVIEW], true);
    }
}
