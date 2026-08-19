<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Support\PermissionLabels;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Upserts ai.* (and keeps intelligence.*) permissions on every tenant DB,
 * then attaches them to the owner role.
 */
final class SeedAiAssistantPermissionsCommand extends Command
{
    protected $signature = 'dressnmore:seed-ai-permissions {--tenant= : Optional tenant id or slug}';

    protected $description = 'Seed AI Assistant permissions into tenant databases and attach to owner role';

    public function handle(): int
    {
        $aiKeys = array_values(array_filter(
            array_keys(PermissionLabels::all()),
            static fn (string $key): bool => str_starts_with($key, 'ai.') || str_starts_with($key, 'smart_assistant.')
        ));

        if ($aiKeys === []) {
            $this->error('No ai.* / smart_assistant.* keys found in PermissionLabels.');

            return self::FAILURE;
        }

        $query = Tenant::query()->orderBy('id');
        $filter = $this->option('tenant');
        if (is_string($filter) && $filter !== '') {
            $query->where(function ($q) use ($filter): void {
                if (ctype_digit($filter)) {
                    $q->where('id', (int) $filter);
                }
                $q->orWhere('slug', $filter);
            });
        }

        $ok = 0;
        $fail = 0;

        foreach ($query->cursor() as $tenant) {
            /** @var Tenant $tenant */
            try {
                $this->seedTenant($tenant, $aiKeys);
                $this->line("OK  {$tenant->id} {$tenant->slug}");
                $ok++;
            } catch (Throwable $e) {
                $this->error("FAIL {$tenant->id} {$tenant->slug}: ".$e->getMessage());
                $fail++;
            }
        }

        $this->info("Done. ok={$ok} fail={$fail}");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param list<string> $aiKeys
     */
    private function seedTenant(Tenant $tenant, array $aiKeys): void
    {
        $database = (string) $tenant->database_name;
        if ($database === '') {
            throw new \RuntimeException('Missing database_name');
        }

        Config::set('database.connections.tenant.database', $database);
        DB::purge('tenant');
        DB::reconnect('tenant');

        $permissionIds = [];
        foreach ($aiKeys as $key) {
            $id = DB::connection('tenant')->table('permissions')->where('key', $key)->value('id');
            if ($id === null) {
                $id = DB::connection('tenant')->table('permissions')->insertGetId([
                    'key' => $key,
                    'name' => PermissionLabels::label($key),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::connection('tenant')->table('permissions')->where('id', $id)->update([
                    'name' => PermissionLabels::label($key),
                    'updated_at' => now(),
                ]);
            }
            $permissionIds[] = (int) $id;
        }

        $ownerId = DB::connection('tenant')->table('roles')->where('slug', 'owner')->value('id');
        if ($ownerId === null) {
            return;
        }

        $pivot = $this->resolvePivotTable();
        foreach ($permissionIds as $permissionId) {
            $exists = DB::connection('tenant')->table($pivot)
                ->where('role_id', $ownerId)
                ->where('permission_id', $permissionId)
                ->exists();
            if (! $exists) {
                DB::connection('tenant')->table($pivot)->insert([
                    'role_id' => $ownerId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $managerId = DB::connection('tenant')->table('roles')->where('slug', 'manager')->value('id');
        if ($managerId !== null) {
            $managerKeys = [
                'ai.access',
                'ai.chat',
                'ai.history',
                'ai.usage',
                'smart_assistant.access',
                'smart_assistant.channels',
                'smart_assistant.messages',
                'smart_assistant.comments',
            ];
            $managerPermIds = DB::connection('tenant')->table('permissions')
                ->whereIn('key', $managerKeys)
                ->pluck('id')
                ->all();
            foreach ($managerPermIds as $permissionId) {
                $exists = DB::connection('tenant')->table($pivot)
                    ->where('role_id', $managerId)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (! $exists) {
                    DB::connection('tenant')->table($pivot)->insert([
                        'role_id' => $managerId,
                        'permission_id' => (int) $permissionId,
                    ]);
                }
            }
        }
    }

    private function resolvePivotTable(): string
    {
        $schema = DB::connection('tenant')->getSchemaBuilder();
        foreach (['permission_role', 'role_permission'] as $table) {
            if ($schema->hasTable($table)) {
                return $table;
            }
        }

        throw new \RuntimeException('No role-permission pivot table found');
    }
}
