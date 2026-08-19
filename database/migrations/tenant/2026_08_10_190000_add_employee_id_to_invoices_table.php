<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection($this->connection)->table('invoices', function (Blueprint $table): void {
            if (! Schema::connection($this->connection)->hasColumn('invoices', 'employee_id')) {
                $table->foreignId('employee_id')
                    ->nullable()
                    ->after('branch_id')
                    ->constrained('hr_employees')
                    ->nullOnDelete();
                $table->index('employee_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('invoices', function (Blueprint $table): void {
            if (Schema::connection($this->connection)->hasColumn('invoices', 'employee_id')) {
                $table->dropConstrainedForeignId('employee_id');
            }
        });
    }
};
