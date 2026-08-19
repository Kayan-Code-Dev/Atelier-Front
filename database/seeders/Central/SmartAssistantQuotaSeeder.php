<?php

namespace Database\Seeders\Central;

use App\Models\Central\Plan;
use App\Models\Central\PlanFeature;
use App\Support\PlanFeatureCatalog;
use Illuminate\Database\Seeder;

/** Insert missing Smart Assistant plan keys without wiping other features. */
final class SmartAssistantQuotaSeeder extends Seeder
{
    public function run(): void
    {
        $keys = [
            'smart_assistant.enabled',
            'smart_assistant.auto_reply',
            'smart_assistant.messages_monthly.max',
        ];

        foreach (Plan::query()->get() as $plan) {
            $slug = PlanFeatureCatalog::normalizePlanSlug((string) $plan->slug);
            $defaults = PlanFeatureCatalog::defaultMatrix()[$slug]
                ?? PlanFeatureCatalog::defaultMatrix()[PlanFeatureCatalog::PLAN_STARTER]
                ?? [];

            foreach ($keys as $key) {
                if (! in_array($key, PlanFeatureCatalog::keys(), true)) {
                    continue;
                }
                $exists = PlanFeature::query()
                    ->where('plan_id', $plan->id)
                    ->where('feature_key', $key)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $value = $defaults[$key] ?? (PlanFeatureCatalog::isBooleanKey($key) ? false : 0);
                PlanFeature::query()->create([
                    'plan_id' => $plan->id,
                    'feature_key' => $key,
                    'feature_value' => PlanFeatureCatalog::normalizeValue($key, $value),
                    'value_type' => PlanFeatureCatalog::valueType($key),
                ]);
            }
        }
    }
}
