<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('smart_assistant_channel_connections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('channel_type', 32)->index();
            $table->string('external_account_id', 120)->nullable()->index(); // phone_number_id for WA
            $table->string('waba_id', 120)->nullable();
            $table->string('display_name', 190)->nullable();
            $table->text('access_token')->nullable(); // encrypted via cast
            $table->string('webhook_verify_token', 255)->nullable();
            $table->string('app_secret', 255)->nullable(); // optional per-tenant override
            $table->string('status', 32)->default('disconnected')->index();
            $table->boolean('auto_reply_enabled')->default(false);
            $table->string('auto_reply_mode', 32)->default('template'); // template|planner|off
            $table->json('meta')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'channel_type'], 'sa_conn_tenant_channel_unique');
            $table->unique(['channel_type', 'external_account_id'], 'sa_conn_channel_external_unique');
        });

        Schema::connection('central')->create('smart_assistant_inbound_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('channel_type', 32)->index();
            $table->string('external_message_id', 190);
            $table->string('from_id', 120)->nullable();
            $table->text('text')->nullable();
            $table->string('status', 32)->default('received'); // received|replied|ignored|failed
            $table->json('payload')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_type', 'external_message_id'], 'sa_inbound_channel_msgid_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('smart_assistant_inbound_messages');
        Schema::connection('central')->dropIfExists('smart_assistant_channel_connections');
    }
};
