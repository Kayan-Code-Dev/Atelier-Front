<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementLine extends BaseTenantModel
{
    protected $fillable = [
        'import_id',
        'bank_account_id',
        'line_date',
        'description',
        'reference',
        'debit',
        'credit',
        'amount',
        'fingerprint',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'line_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'amount' => 'decimal:2',
            'raw_payload' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'import_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BankReconciliationMatch::class, 'statement_line_id');
    }

    public function signedAmount(): float
    {
        return round((float) $this->amount, 2);
    }
}
