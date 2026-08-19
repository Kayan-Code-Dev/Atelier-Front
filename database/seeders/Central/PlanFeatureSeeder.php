<?php

namespace Database\Seeders\Central;

use App\Models\Central\Plan;
use App\Services\Platform\PlanService;
use App\Support\PlanFeatureCatalog;
use Illuminate\Database\Seeder;

class PlanFeatureSeeder extends Seeder
{
    public function run(): void
    {
        /** @var PlanService $planService */
        $planService = app(PlanService::class);

        foreach (PlanFeatureCatalog::defaultMatrix() as $slug => $features) {
            $plan = Plan::query()->where('slug', $slug)->first();
            if (! $plan) {
                continue;
            }

            $planService->syncFeatures($plan, $features);
        }
    }
}
