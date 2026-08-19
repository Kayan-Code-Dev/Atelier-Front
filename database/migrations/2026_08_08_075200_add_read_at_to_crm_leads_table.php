<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('crm_leads')) {
            return;
        }

        if (! Schema::connection('central')->hasColumn('crm_leads', 'read_at')) {
            Schema::connection('central')->table('crm_leads', function (Blueprint $table): void {
                $table->timestamp('read_at')->nullable()->after('last_message');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('crm_leads')) {
            return;
        }

        if (Schema::connection('central')->hasColumn('crm_leads', 'read_at')) {
            Schema::connection('central')->table('crm_leads', function (Blueprint $table): void {
                $table->dropColumn('read_at');
            });
        }
    }
};
