<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('smart_assistant_quota_usages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('period', 7)->index(); // Y-m
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'period'], 'sa_quota_tenant_period_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('smart_assistant_quota_usages');
    }
};
