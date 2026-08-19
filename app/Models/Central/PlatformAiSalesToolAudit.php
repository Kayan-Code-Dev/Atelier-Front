<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class PlatformAiSalesToolAudit extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_ai_sales_tool_audits';

    protected $fillable = [
        'tool', 'lead_id', 'conversation_id', 'input_summary', 'result_summary', 'success',
    ];

    protected function casts(): array
    {
        return [
            'input_summary' => 'array',
            'result_summary' => 'array',
            'success' => 'boolean',
        ];
    }
}
