<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class AiSalesKnowledgeItem extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_ai_sales_knowledge';

    protected $fillable = [
        'title', 'content', 'category', 'status', 'source',
    ];
}
