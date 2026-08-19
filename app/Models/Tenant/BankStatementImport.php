<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends BaseTenantModel
{
    public const STATUS_PREVIEW = 'preview';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'bank_account_id',
        'reconciliation_id',
        'branch_id',
        'original_filename',
        'storage_path',
        'checksum',
        'row_count',
        'status',
        'imported_by',
        'imported_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class, 'import_id');
    }
}
