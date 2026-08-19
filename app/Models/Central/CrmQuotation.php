<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmQuotation extends Model
{
    use SoftDeletes;

    protected $connection = 'central';

    protected $table = 'crm_quotations';

    protected $fillable = [
        'lead_id', 'number', 'lead_name', 'atelier_name', 'plan_name',
        'amount', 'discount', 'status', 'valid_until', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'valid_until' => 'date',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CrmQuotationItem::class, 'quotation_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'created_by');
    }
}
