<?php

namespace Database\Seeders\Tenant;

use App\Enums\CustomerStatus;
use App\Models\Tenant\DressCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DressCategorySeeder extends Seeder
{
    /**
     * Default catalog seeded for every new tenant.
     *
     * @var array<string, array{slug: string, children: list<array{name: string, slug: string}>}>
     */
    public const TREE = [
        'فستان' => [
            'slug' => 'dress',
            'children' => [
                ['name' => 'تركي', 'slug' => 'dress-turkish'],
                ['name' => 'فرنسي', 'slug' => 'dress-french'],
                ['name' => 'هاند ميد', 'slug' => 'dress-handmade'],
            ],
        ],
        'زفاف' => [
            'slug' => 'wedding',
            'children' => [
                ['name' => 'تركي', 'slug' => 'wedding-turkish'],
                ['name' => 'فرنسي', 'slug' => 'wedding-french'],
                ['name' => 'هاند ميد', 'slug' => 'wedding-handmade'],
            ],
        ],
        'خطوبة' => [
            'slug' => 'engagement',
            'children' => [
                ['name' => 'كريمي', 'slug' => 'engagement-creamy'],
                ['name' => 'هاند ميد', 'slug' => 'engagement-handmade'],
            ],
        ],
    ];

    public function run(): void
    {
        if (! Schema::connection('tenant')->hasTable('dress_categories')) {
            return;
        }

        foreach (self::TREE as $parentName => $meta) {
            $parent = DressCategory::query()
                ->whereNull('parent_id')
                ->where(function ($query) use ($parentName, $meta): void {
                    $query->where('name', $parentName)
                        ->orWhere('slug', $meta['slug']);
                })
                ->first();

            if ($parent === null) {
                $parent = DressCategory::query()->create([
                    'parent_id' => null,
                    'name' => $parentName,
                    'slug' => $meta['slug'],
                    'status' => CustomerStatus::ACTIVE->value,
                    'description' => null,
                ]);
            }

            foreach ($meta['children'] as $child) {
                $exists = DressCategory::query()
                    ->where('parent_id', $parent->id)
                    ->where(function ($query) use ($child): void {
                        $query->where('name', $child['name'])
                            ->orWhere('slug', $child['slug']);
                    })
                    ->exists();

                if ($exists) {
                    continue;
                }

                DressCategory::query()->create([
                    'parent_id' => $parent->id,
                    'name' => $child['name'],
                    'slug' => $child['slug'],
                    'status' => CustomerStatus::ACTIVE->value,
                    'description' => null,
                ]);
            }
        }
    }
}
