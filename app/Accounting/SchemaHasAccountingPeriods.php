<?php

namespace App\Accounting;

use Illuminate\Support\Facades\Schema;

final class SchemaHasAccountingPeriods
{
    public static function exists(): bool
    {
        return Schema::connection('tenant')->hasTable('accounting_periods');
    }
}
