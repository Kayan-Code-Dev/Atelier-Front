<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Support\PermissionLabels;
use Illuminate\Database\Seeder;

class TenantRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $ownerRole = Role::query()->updateOrCreate(
            ['slug' => 'owner'],
            ['name' => 'المالك']
        );

        $permissionKeys = array_keys(PermissionLabels::all());

        $permissionIds = [];
        foreach ($permissionKeys as $key) {
            $permission = Permission::query()->updateOrCreate(
                ['key' => $key],
                ['name' => PermissionLabels::label($key)]
            );
            $permissionIds[] = $permission->id;
        }

        $ownerRole->permissions()->sync($permissionIds);

        $journalPermissionIds = Permission::query()
            ->whereIn('key', [
                'accounting.journal_entries.view',
                'accounting.journal_entries.create',
                'accounting.journal_entries.update',
                'accounting.journal_entries.approve',
                'accounting.journal_entries.cancel',
                'accounting.journal_entries.reverse',
                'accounting.journal_entries.export',
            ])
            ->pluck('id')
            ->all();

        $sprint3ViewIds = Permission::query()
            ->whereIn('key', [
                'accounting.assets.view',
                'accounting.equity.view',
                'accounting.liabilities.view',
            ])
            ->pluck('id')
            ->all();

        $sprint5ViewIds = Permission::query()
            ->whereIn('key', [
                'accounting.reconciliation.view',
                'accounting.receivables.view',
                'accounting.payables.view',
            ])
            ->pluck('id')
            ->all();

        Role::query()
            ->where('slug', '!=', 'owner')
            ->whereHas('permissions', fn ($query) => $query->where('key', 'accounting.view'))
            ->each(function (Role $role) use ($journalPermissionIds, $sprint3ViewIds, $sprint5ViewIds): void {
                $role->permissions()->syncWithoutDetaching(array_merge($journalPermissionIds, $sprint3ViewIds, $sprint5ViewIds));
            });

        $managerPermissionIds = Permission::query()
            ->whereIn('key', [
                'tailoring.view',
                'tailoring.update',
                'tailoring.change_stage',
                'tailoring.view_workshop',
                'tailoring.view_schedule',
                'invoices.view',
                'invoices.create',
                'customers.view',
                'customers.create',
                'ai.access',
                'ai.chat',
                'ai.history',
                'ai.usage',
                'smart_assistant.access',
                'smart_assistant.channels',
                'smart_assistant.messages',
                'smart_assistant.comments',
            ])
            ->pluck('id')
            ->all();

        $managerRole = Role::query()->updateOrCreate(
            ['slug' => 'manager'],
            ['name' => 'مدير']
        );
        $managerRole->permissions()->syncWithoutDetaching($managerPermissionIds);

        $this->call(AccountSeeder::class);
        $this->call(FixedAssetCategorySeeder::class);
        $this->call(DressCategorySeeder::class);
    }
}
