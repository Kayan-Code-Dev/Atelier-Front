<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('crm_leads', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('crm_leads', 'importance')) {
                $table->string('importance', 20)->default('medium')->after('temperature');
            }
            if (! Schema::connection('central')->hasColumn('crm_leads', 'expected_plan')) {
                $table->string('expected_plan')->nullable()->after('offer_value');
            }
        });

        Schema::connection('central')->table('crm_follow_ups', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('crm_follow_ups', 'kind')) {
                $table->string('kind', 30)->default('follow_up')->after('lead_id'); // call|follow_up
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('crm_leads', function (Blueprint $table): void {
            $table->dropColumn(['importance', 'expected_plan']);
        });
        Schema::connection('central')->table('crm_follow_ups', function (Blueprint $table): void {
            $table->dropColumn(['kind']);
        });
    }
};
