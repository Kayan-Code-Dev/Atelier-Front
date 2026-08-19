<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('crm_leads', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('crm_leads', 'pain_points')) {
                $table->json('pain_points')->nullable();
            }
            if (! Schema::connection('central')->hasColumn('crm_leads', 'purchase_intent_level')) {
                $table->string('purchase_intent_level', 40)->nullable();
            }
            if (! Schema::connection('central')->hasColumn('crm_leads', 'invoice_volume')) {
                $table->unsignedInteger('invoice_volume')->nullable();
            }
        });

        Schema::connection('central')->table('platform_ai_sales_conversations', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('platform_ai_sales_conversations', 'sales_state')) {
                $table->string('sales_state', 32)->nullable();
            }
            if (! Schema::connection('central')->hasColumn('platform_ai_sales_conversations', 'sales_memory')) {
                $table->json('sales_memory')->nullable();
            }
        });

        if (! Schema::connection('central')->hasTable('platform_ai_sales_tool_audits')) {
            Schema::connection('central')->create('platform_ai_sales_tool_audits', function (Blueprint $table): void {
                $table->id();
                $table->string('tool', 80);
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->unsignedBigInteger('conversation_id')->nullable();
                $table->json('input_summary')->nullable();
                $table->json('result_summary')->nullable();
                $table->boolean('success')->default(true);
                $table->timestamps();
                $table->index(['lead_id', 'created_at']);
                $table->index('tool');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_ai_sales_tool_audits');
        Schema::connection('central')->table('platform_ai_sales_conversations', function (Blueprint $table): void {
            foreach (['sales_state', 'sales_memory'] as $col) {
                if (Schema::connection('central')->hasColumn('platform_ai_sales_conversations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::connection('central')->table('crm_leads', function (Blueprint $table): void {
            foreach (['pain_points', 'purchase_intent_level', 'invoice_volume'] as $col) {
                if (Schema::connection('central')->hasColumn('crm_leads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
