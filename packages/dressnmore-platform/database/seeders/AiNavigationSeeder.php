<?php

declare(strict_types=1);

namespace DressnMore\Platform\Database\Seeders;

use DressnMore\Platform\Domain\AiNavigation;
use Illuminate\Database\Seeder;

final class AiNavigationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (AiNavigation::items() as $item) {
            $this->command?->info(sprintf(
                'AI nav: %s → %s (%s)',
                $item['key'],
                $item['path'],
                $item['permission']
            ));
        }
    }
}
