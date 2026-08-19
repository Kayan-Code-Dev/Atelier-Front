<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('platform_ai_sales_knowledge', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->string('category', 32);
            $table->string('status', 24)->default('draft');
            $table->string('source', 64)->default('manual');
            $table->timestamps();
            $table->index(['category', 'status']);
        });

        Schema::connection('central')->create('platform_ai_sales_agent_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Sara');
            $table->string('avatar_path')->nullable();
            $table->string('default_language', 8)->default('ar');
            $table->json('supported_languages')->nullable();
            $table->json('personality')->nullable();
            $table->json('sales_behavior')->nullable();
            $table->json('handoff_rules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_ai_sales_knowledge');
        Schema::connection('central')->dropIfExists('platform_ai_sales_agent_settings');
    }
};
