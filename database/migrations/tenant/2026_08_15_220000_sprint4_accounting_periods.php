<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('tenant')->hasTable('accounting_periods')) {
            return;
        }

        Schema::connection('tenant')->table('accounting_periods', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('accounting_periods', 'name')) {
                $table->string('name')->nullable();
            }
            if (! Schema::connection('tenant')->hasColumn('accounting_periods', 'month')) {
                $table->unsignedTinyInteger('month')->nullable();
            }
            if (! Schema::connection('tenant')->hasColumn('accounting_periods', 'status')) {
                $table->string('status', 16)->default('open');
            }
            if (! Schema::connection('tenant')->hasColumn('accounting_periods', 'reopen_reason')) {
                $table->text('reopen_reason')->nullable();
            }
            if (! Schema::connection('tenant')->hasColumn('accounting_periods', 'reopened_by')) {
                $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::connection('tenant')->hasColumn('accounting_periods', 'reopened_at')) {
                $table->timestamp('reopened_at')->nullable();
            }
            if (! Schema::connection('tenant')->hasColumn('accounting_periods', 'locked_by')) {
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::connection('tenant')->hasColumn('accounting_periods', 'locked_at')) {
                $table->timestamp('locked_at')->nullable();
            }
        });

        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
            7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        $rows = DB::connection('tenant')->table('accounting_periods')->get();
        foreach ($rows as $row) {
            $startsOn = (string) $row->starts_on;
            $month = (int) substr($startsOn, 5, 2);
            $year = (int) ($row->year ?: substr($startsOn, 0, 4));
            $closed = (bool) $row->is_closed;
            $status = $closed ? 'closed' : 'open';
            if (property_exists($row, 'status') && in_array((string) $row->status, ['closed', 'locked'], true)) {
                $status = (string) $row->status;
            }

            DB::connection('tenant')->table('accounting_periods')->where('id', $row->id)->update([
                'month' => $month > 0 ? $month : null,
                'name' => ($months[$month] ?? 'فترة').' '.$year,
                'status' => $status,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::connection('tenant')->hasTable('accounting_periods')) {
            return;
        }

        Schema::connection('tenant')->table('accounting_periods', function (Blueprint $table): void {
            foreach (['locked_at', 'locked_by', 'reopened_at', 'reopened_by', 'reopen_reason', 'status', 'month', 'name'] as $column) {
                if (Schema::connection('tenant')->hasColumn('accounting_periods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
