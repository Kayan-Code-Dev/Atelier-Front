<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('platform_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('module', 64)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('color', 32)->default('teal');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_permission_role', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
            $table->foreign('permission_id')->references('id')->on('platform_permissions')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('platform_roles')->cascadeOnDelete();
        });

        Schema::connection('central')->table('super_admins', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('super_admins', 'platform_role_id')) {
                $table->unsignedBigInteger('platform_role_id')->nullable()->after('status');
                $table->foreign('platform_role_id')->references('id')->on('platform_roles')->nullOnDelete();
            }
            if (! Schema::connection('central')->hasColumn('super_admins', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('platform_role_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('super_admins', function (Blueprint $table): void {
            if (Schema::connection('central')->hasColumn('super_admins', 'platform_role_id')) {
                $table->dropForeign(['platform_role_id']);
                $table->dropColumn('platform_role_id');
            }
            if (Schema::connection('central')->hasColumn('super_admins', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
        });

        Schema::connection('central')->dropIfExists('platform_permission_role');
        Schema::connection('central')->dropIfExists('platform_roles');
        Schema::connection('central')->dropIfExists('platform_permissions');
    }
};
