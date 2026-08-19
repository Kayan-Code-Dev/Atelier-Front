<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class CrmLookup extends Model
{
    protected $connection = 'central';

    protected $table = 'crm_lookups';

    protected $fillable = ['type', 'key', 'label', 'color', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
