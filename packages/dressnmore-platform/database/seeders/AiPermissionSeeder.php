<?php

declare(strict_types=1);

namespace DressnMore\Platform\Database\Seeders;

use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use DressnMore\Platform\Support\AiPermissionCatalog;
use Illuminate\Database\Seeder;

final class AiPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $ids = [];
        foreach (AiPermissionCatalog::definitions() as $key => $label) {
            $permission = Permission::query()->updateOrCreate(
                ['key' => $key],
                ['name' => $label]
            );
            $ids[] = $permission->id;
        }

        $owner = Role::query()->where('slug', 'owner')->first();
        if ($owner !== null) {
            $owner->permissions()->syncWithoutDetaching($ids);
        }

        $manager = Role::query()->updateOrCreate(
            ['slug' => 'manager'],
            ['name' => 'مدير']
        );
        $managerIds = Permission::query()
            ->whereIn('key', ['ai.access', 'ai.chat', 'ai.history', 'ai.usage'])
            ->pluck('id')
            ->all();
        $manager->permissions()->syncWithoutDetaching($managerIds);
    }
}
