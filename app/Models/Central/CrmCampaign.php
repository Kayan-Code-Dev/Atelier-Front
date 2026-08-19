<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmCampaign extends Model
{
    use SoftDeletes;

    protected $connection = 'central';

    protected $table = 'crm_campaigns';

    protected $fillable = [
        'name', 'channel', 'budget', 'spent', 'messages', 'qualified_leads',
        'sales', 'revenue', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'spent' => 'decimal:2',
            'revenue' => 'decimal:2',
            'is_active' => 'boolean',
            'messages' => 'integer',
            'qualified_leads' => 'integer',
            'sales' => 'integer',
        ];
    }

    public function getCplAttribute(): float
    {
        $qualified = max(0, (int) $this->qualified_leads);
        if ($qualified === 0) {
            return 0;
        }

        return round((float) $this->spent / $qualified, 2);
    }

    public function getCpsAttribute(): float
    {
        $sales = max(0, (int) $this->sales);
        if ($sales === 0) {
            return 0;
        }

        return round((float) $this->spent / $sales, 2);
    }

    public function getRoiAttribute(): float
    {
        $spent = (float) $this->spent;
        if ($spent <= 0) {
            return 0;
        }

        return round((((float) $this->revenue - $spent) / $spent) * 100, 1);
    }
}
