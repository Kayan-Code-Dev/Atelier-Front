<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmQuotationItem extends Model
{
    protected $connection = 'central';

    protected $table = 'crm_quotation_items';

    protected $fillable = ['quotation_id', 'label', 'price', 'sort_order'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(CrmQuotation::class, 'quotation_id');
    }
}
