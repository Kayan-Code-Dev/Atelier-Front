<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('users', 'branch_id')) {
            Schema::connection($this->connection)->table('users', function (Blueprint $table): void {
                $table->foreignId('branch_id')->nullable()->after('phone')->constrained('branches')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasColumn('users', 'branch_id')) {
            Schema::connection($this->connection)->table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
