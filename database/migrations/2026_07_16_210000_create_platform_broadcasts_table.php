<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('platform_broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('target_type', 32)->default('all');
            $table->json('target_plans')->nullable();
            $table->json('target_statuses')->nullable();
            $table->json('channels');
            $table->string('priority', 16)->default('normal');
            $table->string('status', 16)->default('sent');
            $table->string('target_detail')->nullable();
            $table->unsignedInteger('tenants_targeted')->default(0);
            $table->unsignedInteger('tenants_delivered')->default(0);
            $table->unsignedInteger('tenants_failed')->default(0);
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->foreign('sent_by')->references('id')->on('super_admins')->nullOnDelete();
            $table->index(['status', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_broadcasts');
    }
};
