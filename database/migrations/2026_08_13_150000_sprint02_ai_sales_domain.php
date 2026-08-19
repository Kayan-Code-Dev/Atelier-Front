<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('crm_leads', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('crm_leads', 'intent')) {
                $table->string('intent', 40)->nullable()->after('notes');
            }
            if (! Schema::connection('central')->hasColumn('crm_leads', 'handoff_status')) {
                $table->string('handoff_status', 32)->default('AI_ACTIVE')->after('intent');
            }
            if (! Schema::connection('central')->hasColumn('crm_leads', 'objection')) {
                $table->string('objection')->nullable()->after('handoff_status');
            }
            if (! Schema::connection('central')->hasColumn('crm_leads', 'current_software')) {
                $table->string('current_software')->nullable()->after('objection');
            }
            if (! Schema::connection('central')->hasColumn('crm_leads', 'score_signals')) {
                $table->json('score_signals')->nullable()->after('current_software');
            }
        });

        Schema::connection('central')->table('crm_follow_ups', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('crm_follow_ups', 'conversation_id')) {
                $table->unsignedBigInteger('conversation_id')->nullable()->after('lead_id');
            }
            if (! Schema::connection('central')->hasColumn('crm_follow_ups', 'channel')) {
                $table->string('channel', 32)->nullable()->after('kind');
            }
            if (! Schema::connection('central')->hasColumn('crm_follow_ups', 'message_intent')) {
                $table->string('message_intent', 40)->nullable()->after('reason');
            }
            if (! Schema::connection('central')->hasColumn('crm_follow_ups', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('assigned_to');
            }
        });

        if (! Schema::connection('central')->hasTable('platform_ai_sales_conversations')) {
            Schema::connection('central')->create('platform_ai_sales_conversations', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
                $table->string('channel', 32)->default('website');
                $table->string('external_id')->nullable();
                $table->string('ownership', 24)->default('ai');
                $table->string('handoff_status', 32)->default('AI_ACTIVE');
                $table->string('status', 32)->default('new');
                $table->string('intent', 40)->nullable();
                $table->string('sentiment', 24)->nullable();
                $table->text('summary')->nullable();
                $table->text('last_message')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();
                $table->index(['channel', 'external_id']);
                $table->index(['lead_id', 'handoff_status']);
                $table->index('last_activity_at');
            });
        }

        if (! Schema::connection('central')->hasTable('platform_ai_sales_messages')) {
            Schema::connection('central')->create('platform_ai_sales_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('conversation_id')->constrained('platform_ai_sales_conversations')->cascadeOnDelete();
                $table->string('author', 24);
                $table->text('body');
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['conversation_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_ai_sales_messages');
        Schema::connection('central')->dropIfExists('platform_ai_sales_conversations');

        Schema::connection('central')->table('crm_follow_ups', function (Blueprint $table): void {
            foreach (['conversation_id', 'channel', 'message_intent', 'created_by'] as $col) {
                if (Schema::connection('central')->hasColumn('crm_follow_ups', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::connection('central')->table('crm_leads', function (Blueprint $table): void {
            foreach (['intent', 'handoff_status', 'objection', 'current_software', 'score_signals'] as $col) {
                if (Schema::connection('central')->hasColumn('crm_leads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
