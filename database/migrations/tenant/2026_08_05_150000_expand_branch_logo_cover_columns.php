<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $db = DB::connection($this->connection);

        if ($schema->hasColumn('branches', 'image')) {
            $db->statement('ALTER TABLE `branches` MODIFY `image` LONGTEXT NULL');
        }
        if ($schema->hasColumn('branches', 'logo')) {
            $db->statement('ALTER TABLE `branches` MODIFY `logo` LONGTEXT NULL');
        } else {
            $db->statement('ALTER TABLE `branches` ADD `logo` LONGTEXT NULL AFTER `image`');
        }
        if ($schema->hasColumn('branches', 'cover')) {
            $db->statement('ALTER TABLE `branches` MODIFY `cover` LONGTEXT NULL');
        } else {
            $db->statement('ALTER TABLE `branches` ADD `cover` LONGTEXT NULL AFTER `logo`');
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $db = DB::connection($this->connection);

        if ($schema->hasColumn('branches', 'image')) {
            $db->statement('ALTER TABLE `branches` MODIFY `image` VARCHAR(255) NULL');
        }
        if ($schema->hasColumn('branches', 'logo')) {
            $db->statement('ALTER TABLE `branches` MODIFY `logo` VARCHAR(255) NULL');
        }
        if ($schema->hasColumn('branches', 'cover')) {
            $db->statement('ALTER TABLE `branches` MODIFY `cover` VARCHAR(255) NULL');
        }
    }
};
