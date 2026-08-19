<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liability extends BaseTenantModel
{
    public const TYPE_SUPPLIER_PAYABLE = 'supplier_payable';

    public const TYPE_LOAN = 'loan';

    public const TYPE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SETTLED = 'settled';

    protected $fillable = [
        'type',
        'lender',
        'number',
        'name',
        'principal',
        'start_date',
        'due_date',
        'status',
        'branch_id',
        'liability_account_id',
        'cash_account_id',
        'notes',
        'receipt_journal_entry_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'principal' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function settlements(): HasMany
    {
        return $this->hasMany(LoanSettlement::class);
    }

    public function receiptJournal(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'receipt_journal_entry_id');
    }

    public function liabilityAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'liability_account_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
