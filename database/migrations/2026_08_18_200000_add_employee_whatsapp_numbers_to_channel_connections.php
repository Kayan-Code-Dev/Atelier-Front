<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'smart_assistant_channel_connections';
        Schema::connection('central')->table($tableName, function (Blueprint $table) use ($tableName): void {
            if (! Schema::connection('central')->hasColumn($tableName, 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('tenant_id');
            }
            if (! Schema::connection('central')->hasColumn($tableName, 'assistant_name')) {
                $table->string('assistant_name', 120)->nullable();
            }
            if (! Schema::connection('central')->hasColumn($tableName, 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable();
            }
            if (! Schema::connection('central')->hasColumn($tableName, 'department_name')) {
                $table->string('department_name', 120)->nullable();
            }
            if (! Schema::connection('central')->hasColumn($tableName, 'session_key')) {
                $table->string('session_key', 64)->nullable();
            }
        });

        try {
            Schema::connection('central')->table($tableName, function (Blueprint $table): void {
                $table->index(['tenant_id', 'user_id'], 'sa_conn_tenant_user_idx');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::connection('central')->table($tableName, function (Blueprint $table): void {
                $table->unique('session_key', 'sa_conn_session_key_unique');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::connection('central')->table($tableName, function (Blueprint $table): void {
                $table->dropUnique('sa_conn_tenant_channel_unique');
            });
        } catch (\Throwable) {
        }

        $rows = DB::connection('central')->table('smart_assistant_channel_connections')
            ->where('channel_type', 'whatsapp')
            ->whereNull('session_key')
            ->get(['id', 'tenant_id']);

        foreach ($rows as $row) {
            DB::connection('central')->table('smart_assistant_channel_connections')
                ->where('id', $row->id)
                ->update(['session_key' => (string) $row->tenant_id]);
        }
    }

    public function down(): void
    {
        Schema::connection('central')->table('smart_assistant_channel_connections', function (Blueprint $table): void {
            $table->dropUnique('sa_conn_session_key_unique');
            $table->dropIndex('sa_conn_tenant_user_idx');
            $table->dropColumn([
                'user_id',
                'assistant_name',
                'department_id',
                'department_name',
                'session_key',
            ]);
            $table->unique(['tenant_id', 'channel_type'], 'sa_conn_tenant_channel_unique');
        });
    }
};
