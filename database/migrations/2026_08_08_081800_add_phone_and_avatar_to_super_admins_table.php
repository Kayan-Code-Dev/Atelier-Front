<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('super_admins')) {
            return;
        }

        Schema::connection('central')->table('super_admins', function (Blueprint $table): void {
            if (! Schema::connection('central')->hasColumn('super_admins', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }
            if (! Schema::connection('central')->hasColumn('super_admins', 'avatar_data')) {
                $table->longText('avatar_data')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('super_admins')) {
            return;
        }

        Schema::connection('central')->table('super_admins', function (Blueprint $table): void {
            if (Schema::connection('central')->hasColumn('super_admins', 'avatar_data')) {
                $table->dropColumn('avatar_data');
            }
            if (Schema::connection('central')->hasColumn('super_admins', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
