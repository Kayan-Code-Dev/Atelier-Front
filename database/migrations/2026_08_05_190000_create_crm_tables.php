<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('crm_lookups', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 50); // source|status|activity|governorate|temperature
            $table->string('key', 100)->nullable();
            $table->string('label');
            $table->string('color', 40)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['type', 'label']);
            $table->index(['type', 'is_active', 'sort_order']);
        });

        Schema::connection('central')->create('crm_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('crm_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('facebook')->nullable();
            $table->string('atelier_name')->nullable();
            $table->string('governorate', 100)->nullable();
            $table->string('activity', 100)->nullable();
            $table->unsignedInteger('branches_count')->default(1);
            $table->unsignedInteger('employees_count')->default(1);
            $table->string('source', 100)->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('temperature', 20)->default('cold'); // cold|warm|hot
            $table->string('status', 40)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->text('last_message')->nullable();
            $table->decimal('offer_value', 12, 2)->default(0);
            $table->unsignedTinyInteger('close_probability')->default(0);
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'temperature']);
            $table->index(['assigned_to', 'next_follow_up_at']);
            $table->index('source');
        });

        Schema::connection('central')->create('crm_lead_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamps();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::connection('central')->create('crm_lead_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::connection('central')->create('crm_lead_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->string('mime', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::connection('central')->create('crm_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->string('priority', 20)->default('normal'); // urgent|high|normal|low
            $table->timestamp('due_at');
            $table->string('reason')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending|done|cancelled
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'due_at']);
            $table->index('assigned_to');
        });

        Schema::connection('central')->create('crm_quotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('lead_name')->nullable();
            $table->string('atelier_name')->nullable();
            $table->string('plan_name')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->date('valid_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('central')->create('crm_quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained('crm_quotations')->cascadeOnDelete();
            $table->string('label');
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection('central')->create('crm_deals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->string('title');
            $table->string('lead_name')->nullable();
            $table->decimal('value', 12, 2)->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->string('temperature', 20)->default('warm');
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('stage', 40)->default('new'); // new|qualified|negotiation|won|lost
            $table->foreignId('assigned_to')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('stage');
        });

        Schema::connection('central')->create('crm_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('channel', 50)->nullable();
            $table->decimal('budget', 12, 2)->default(0);
            $table->decimal('spent', 12, 2)->default(0);
            $table->unsignedInteger('messages')->default(0);
            $table->unsignedInteger('qualified_leads')->default(0);
            $table->unsignedInteger('sales')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('crm_campaigns');
        Schema::connection('central')->dropIfExists('crm_deals');
        Schema::connection('central')->dropIfExists('crm_quotation_items');
        Schema::connection('central')->dropIfExists('crm_quotations');
        Schema::connection('central')->dropIfExists('crm_follow_ups');
        Schema::connection('central')->dropIfExists('crm_lead_attachments');
        Schema::connection('central')->dropIfExists('crm_lead_notes');
        Schema::connection('central')->dropIfExists('crm_lead_events');
        Schema::connection('central')->dropIfExists('crm_leads');
        Schema::connection('central')->dropIfExists('crm_settings');
        Schema::connection('central')->dropIfExists('crm_lookups');
    }
};
