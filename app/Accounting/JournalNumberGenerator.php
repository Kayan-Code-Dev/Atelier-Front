<?php

namespace App\Accounting;

use App\Models\Tenant\JournalEntry;
use Illuminate\Support\Carbon;

class JournalNumberGenerator
{
    public function next(Carbon $date): string
    {
        $prefix = 'JE-'.$date->format('Ymd').'-';

        $latest = JournalEntry::query()
            ->where('entry_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('entry_number')
            ->value('entry_number');

        $sequence = 1;
        if (is_string($latest)) {
            $parts = explode('-', $latest);
            $sequence = ((int) end($parts)) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
