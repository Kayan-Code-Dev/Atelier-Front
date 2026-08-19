<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('payments')) {
            return;
        }

        if (! Schema::connection('central')->hasColumn('payments', 'currency')) {
            Schema::connection('central')->table('payments', function (Blueprint $table): void {
                $table->string('currency', 3)->default('EGP')->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('central')->hasColumn('payments', 'currency')) {
            Schema::connection('central')->table('payments', function (Blueprint $table): void {
                $table->dropColumn('currency');
            });
        }
    }
};
