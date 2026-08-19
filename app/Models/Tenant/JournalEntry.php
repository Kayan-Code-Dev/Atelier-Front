<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends BaseTenantModel
{
    public const TYPE_NORMAL = 'normal';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_OPENING = 'opening';

    public const TYPE_CLOSING = 'closing';

    public const TYPE_REVERSAL = 'reversal';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_INVOICE = 'invoice';

    public const SOURCE_SALE = 'sale';

    public const SOURCE_PAYMENT = 'payment';

    public const SOURCE_EXPENSE = 'expense';

    public const SOURCE_RETURN = 'return';

    public const SOURCE_PURCHASE = 'purchase';

    public const SOURCE_PURCHASE_ORDER = 'purchase_order';

    public const SOURCE_SUPPLIER_PAYMENT = 'supplier_payment';

    public const SOURCE_CASH_MOVEMENT = 'cash_movement';

    public const SOURCE_TREASURY = 'treasury';

    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_SECURITY_DEPOSIT_COLLECTION = 'security_deposit_collection';

    public const SOURCE_RENTAL_RETURN_SETTLEMENT = 'rental_return_settlement';

    public const SOURCE_RESERVATION = 'reservation';

    public const SOURCE_PAYROLL = 'payroll';

    public const SOURCE_ASSET = 'asset';

    public const SOURCE_FIXED_ASSET = 'fixed_asset';

    public const SOURCE_ASSET_DISPOSAL = 'fixed_asset_disposal';

    public const SOURCE_DEPRECIATION = 'depreciation';

    public const SOURCE_EQUITY = 'equity';

    public const SOURCE_LOAN = 'loan';

    public const SOURCE_LOAN_SETTLEMENT = 'loan_settlement';

    public const SOURCE_REVERSAL = 'reversal';

    public const SOURCE_ADJUSTMENT = 'adjustment';

    public const SOURCE_OPENING_BALANCE = 'opening_balance';

    public const SOURCE_BANK_RECONCILIATION = 'bank_reconciliation';

    protected $fillable = [
        'entry_number',
        'entry_date',
        'type',
        'source_type',
        'source_id',
        'reference_number',
        'source_reference',
        'description',
        'notes',
        'attachments',
        'status',
        'total_debit',
        'total_credit',
        'difference',
        'is_balanced',
        'branch_id',
        'created_by',
        'submitted_by',
        'approved_by',
        'posted_by',
        'cancelled_by',
        'reversed_by',
        'submitted_at',
        'approved_at',
        'posted_at',
        'cancelled_at',
        'reversed_at',
        'cancellation_reason',
        'reversal_reason',
        'reversed_entry_id',
        'metadata',
        'needs_review',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'difference' => 'decimal:2',
        'is_balanced' => 'boolean',
        'needs_review' => 'boolean',
        'metadata' => 'array',
        'attachments' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    public static function postedStatuses(): array
    {
        return [self::STATUS_POSTED];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_entry_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_entry_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AccountingEvent::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isImmutable(): bool
    {
        return $this->isPosted() || $this->isApproved() || $this->isReversed();
    }

    public function canDelete(): bool
    {
        return $this->isDraft();
    }
}
