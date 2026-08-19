<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection($this->connection)->table('supplier_payments', function (Blueprint $table): void {
            if (! Schema::connection($this->connection)->hasColumn('supplier_payments', 'cashbox_id')) {
                $table->foreignId('cashbox_id')
                    ->nullable()
                    ->after('purchase_order_id')
                    ->constrained('cashboxes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('supplier_payments', function (Blueprint $table): void {
            if (Schema::connection($this->connection)->hasColumn('supplier_payments', 'cashbox_id')) {
                $table->dropConstrainedForeignId('cashbox_id');
            }
        });
    }
};
