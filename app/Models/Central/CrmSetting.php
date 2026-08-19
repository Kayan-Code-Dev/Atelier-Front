<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class CrmSetting extends Model
{
    protected $connection = 'central';

    protected $table = 'crm_settings';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
