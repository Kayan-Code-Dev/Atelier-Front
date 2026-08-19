<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection($this->connection)->table('hr_leave_requests', function (Blueprint $table): void {
            if (! Schema::connection($this->connection)->hasColumn('hr_leave_requests', 'is_paid')) {
                $table->boolean('is_paid')->default(true)->after('days');
            }
            if (! Schema::connection($this->connection)->hasColumn('hr_leave_requests', 'deduction_amount')) {
                $table->decimal('deduction_amount', 12, 2)->nullable()->after('is_paid');
            }
        });

        if (! Schema::connection($this->connection)->hasTable('hr_employee_notes')) {
            Schema::connection($this->connection)->create('hr_employee_notes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('author_name', 120)->nullable();
                $table->string('type', 32)->default('hr');
                $table->text('content');
                $table->timestamps();

                $table->index(['employee_id', 'created_at']);
            });
        }

        Schema::connection($this->connection)->table('hr_payroll_adjustments', function (Blueprint $table): void {
            if (! Schema::connection($this->connection)->hasColumn('hr_payroll_adjustments', 'leave_request_id')) {
                $table->unsignedBigInteger('leave_request_id')->nullable()->after('invoice_id');
                $table->index('leave_request_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('hr_payroll_adjustments', function (Blueprint $table): void {
            if (Schema::connection($this->connection)->hasColumn('hr_payroll_adjustments', 'leave_request_id')) {
                $table->dropIndex(['leave_request_id']);
                $table->dropColumn('leave_request_id');
            }
        });
        Schema::connection($this->connection)->dropIfExists('hr_employee_notes');
        Schema::connection($this->connection)->table('hr_leave_requests', function (Blueprint $table): void {
            $cols = [];
            if (Schema::connection($this->connection)->hasColumn('hr_leave_requests', 'deduction_amount')) {
                $cols[] = 'deduction_amount';
            }
            if (Schema::connection($this->connection)->hasColumn('hr_leave_requests', 'is_paid')) {
                $cols[] = 'is_paid';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
