<?php

declare(strict_types=1);

namespace DressnMore\Platform\Database\Seeders;

use App\Models\Central\Plan;
use App\Models\Central\PlanFeature;
use App\Support\PlanFeatureCatalog;
use Illuminate\Database\Seeder;

/**
 * Upserts AI Assistant plan features without wiping other package features.
 */
final class AiPackageSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            'basic' => [
                'ai_assistant.enabled' => false,
                'ai_assistant.advanced' => false,
                'ai_assistant.chat_monthly.max' => 100,
            ],
            'pro' => [
                'ai_assistant.enabled' => true,
                'ai_assistant.advanced' => false,
                'ai_assistant.chat_monthly.max' => 500,
            ],
            'enterprise' => [
                'ai_assistant.enabled' => true,
                'ai_assistant.advanced' => true,
                'ai_assistant.chat_monthly.max' => 0,
            ],
        ];

        foreach ($matrix as $slug => $features) {
            $plan = Plan::query()->where('slug', $slug)->first();
            if ($plan === null) {
                continue;
            }

            foreach ($features as $key => $value) {
                if (! in_array($key, PlanFeatureCatalog::keys(), true)) {
                    continue;
                }
                PlanFeature::query()->updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_key' => $key,
                    ],
                    [
                        'feature_value' => PlanFeatureCatalog::normalizeValue($key, $value),
                        'value_type' => PlanFeatureCatalog::valueType($key),
                    ]
                );
            }
        }
    }
}
