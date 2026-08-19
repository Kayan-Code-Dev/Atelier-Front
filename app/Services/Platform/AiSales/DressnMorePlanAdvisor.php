<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Contracts\AiSales\DressnMoreSalesContext;
use App\Support\PlanFeatureCatalog;

/**
 * Recommends a DressnMore plan from live catalog entitlements — never invented prices.
 */
final class DressnMorePlanAdvisor
{
    /**
     * @var array<string, string>
     */
    private const FEATURE_ALIASES = [
        'website' => 'website.enabled',
        'marketplace' => 'marketplace.enabled',
        'market' => 'marketplace.enabled',
        'smart consultant' => 'ai_assistant.enabled',
        'smart_consultant' => 'ai_assistant.enabled',
        'ai' => 'ai_assistant.enabled',
        'ai_assistant' => 'ai_assistant.enabled',
        'advanced ai' => 'ai_assistant.advanced',
        'factory' => 'factory.enabled',
        'workshop' => 'workshop.enabled',
        'hr' => 'hr.enabled',
        'accounting' => 'accounting.enabled',
        'smart assistant' => 'smart_assistant.enabled',
        'smart_assistant' => 'smart_assistant.enabled',
    ];

    public function __construct(private readonly DressnMoreSalesContext $context) {}

    /**
     * @param  array{
     *   business_profile?: string|null,
     *   requirements?: list<string>|string|null,
     *   branch_count?: int|string|null,
     *   user_count?: int|string|null,
     *   desired_features?: list<string>|null,
     *   invoice_volume?: int|string|null
     * }  $input
     * @return array<string, mixed>
     */
    public function recommend(array $input): array
    {
        $branches = max(1, (int) ($input['branch_count'] ?? 1));
        $users = max(1, (int) ($input['user_count'] ?? 1));
        $invoices = max(0, (int) ($input['invoice_volume'] ?? 0));
        $desired = $this->normalizeFeatures($input);

        $requiredByFeatures = PlanFeatureCatalog::PLAN_FREE;
        $featureReasons = [];
        foreach ($desired as $featureKey) {
            $min = PlanFeatureCatalog::minimumPlanFor($featureKey);
            $featureReasons[] = [
                'feature' => $featureKey,
                'required_plan' => $min,
                'label' => PlanFeatureCatalog::labelFor($featureKey) ?? $featureKey,
            ];
            if (PlanFeatureCatalog::planRankOf($min) > PlanFeatureCatalog::planRankOf($requiredByFeatures)) {
                $requiredByFeatures = $min;
            }
        }

        $recommended = $requiredByFeatures;
        $matrix = PlanFeatureCatalog::defaultMatrix();
        foreach (PlanFeatureCatalog::publicPlanSlugs() as $slug) {
            if (PlanFeatureCatalog::planRankOf($slug) < PlanFeatureCatalog::planRankOf($requiredByFeatures)) {
                continue;
            }
            $row = $matrix[$slug] ?? [];
            if (! $this->fitsLimits($row, $branches, $users, $invoices)) {
                continue;
            }
            $recommended = $slug;
            break;
        }

        $live = collect($this->context->plans())->firstWhere('slug', $recommended);
        $upgradePath = [];
        foreach (PlanFeatureCatalog::publicPlanSlugs() as $slug) {
            if (PlanFeatureCatalog::planRankOf($slug) >= PlanFeatureCatalog::planRankOf($recommended)) {
                $upgradePath[] = $slug;
            }
        }

        $reasons = [];
        $reasons[] = $branches.' branch(es)';
        $reasons[] = $users.' user(s)';
        foreach ($featureReasons as $row) {
            $reasons[] = ($row['label'] ?? $row['feature']).' requires '.$row['required_plan'];
        }

        $alternative = $this->alternative($recommended, $branches, $users, $desired);
        $knownInputs = ($input['branch_count'] ?? null) !== null && ($input['user_count'] ?? null) !== null;
        $confidence = $desired !== [] && $knownInputs ? 'high' : ($knownInputs ? 'medium' : 'low');

        $reason = $this->reason($recommended, $branches, $users, $desired, $live);

        return [
            'recommended_plan' => $recommended,
            'reason' => $reason,
            'reasons' => $reasons,
            'confidence' => $confidence,
            'alternative_plan' => $alternative,
            'required_plan_for_requested_features' => $requiredByFeatures,
            'upgrade_path' => $upgradePath,
            'live_plan' => $live,
            'pricing_source' => 'subscription_system',
            'inputs' => [
                'branch_count' => $branches,
                'user_count' => $users,
                'invoice_volume' => $invoices,
                'desired_features' => $desired,
                'feature_requirements' => $featureReasons,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    private function normalizeFeatures(array $input): array
    {
        $raw = $input['desired_features'] ?? [];
        if (is_string($input['requirements'] ?? null) && $input['requirements'] !== '') {
            $raw = array_merge((array) $raw, preg_split('/[,;]+/', (string) $input['requirements']) ?: []);
        } elseif (is_array($input['requirements'] ?? null)) {
            $raw = array_merge((array) $raw, $input['requirements']);
        }

        $out = [];
        foreach ((array) $raw as $item) {
            $key = $this->resolveFeatureKey((string) $item);
            if ($key !== null) {
                $out[] = $key;
            }
        }

        return array_values(array_unique($out));
    }

    private function resolveFeatureKey(string $raw): ?string
    {
        $trimmed = strtolower(trim($raw));
        if ($trimmed === '') {
            return null;
        }
        if (PlanFeatureCatalog::definition($trimmed) !== null) {
            return $trimmed;
        }
        if (isset(self::FEATURE_ALIASES[$trimmed])) {
            return self::FEATURE_ALIASES[$trimmed];
        }
        $enabled = $trimmed.'.enabled';
        if (PlanFeatureCatalog::definition($enabled) !== null) {
            return $enabled;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function fitsLimits(array $row, int $branches, int $users, int $invoices): bool
    {
        $branchLimit = (int) ($row['branches.max'] ?? 1);
        $userLimit = (int) ($row['users.max'] ?? 1);
        $invoiceLimit = (int) ($row['invoices.monthly.max'] ?? 0);

        $branchOk = $branchLimit === 0 || $branchLimit >= $branches;
        $userOk = $userLimit === 0 || $userLimit >= $users;
        $invoiceOk = $invoices <= 0 || $invoiceLimit === 0 || $invoiceLimit >= $invoices;

        return $branchOk && $userOk && $invoiceOk;
    }

    /**
     * @param  list<string>  $desired
     * @param  array<string, mixed>|null  $live
     */
    private function reason(string $slug, int $branches, int $users, array $desired, ?array $live): string
    {
        $name = is_string($live['name'] ?? null) ? $live['name'] : ucfirst($slug);
        $price = $live['price'] ?? null;
        $currency = $live['currency'] ?? '';
        $priceBit = $price !== null ? " ({$price} {$currency})" : '';

        return sprintf(
            '%s%s fits %d branch(es), %d user(s)%s.',
            $name,
            $priceBit,
            $branches,
            $users,
            $desired === [] ? '' : ' and the requested modules',
        );
    }

    /**
     * @param  list<string>  $desired
     */
    private function alternative(string $recommended, int $branches, int $users, array $desired): ?string
    {
        if ($recommended === PlanFeatureCatalog::PLAN_PROFESSIONAL && $branches >= 2) {
            return PlanFeatureCatalog::PLAN_STARTER;
        }
        if ($recommended === PlanFeatureCatalog::PLAN_BUSINESS) {
            return PlanFeatureCatalog::PLAN_PROFESSIONAL;
        }
        if ($recommended === PlanFeatureCatalog::PLAN_STARTER && $users <= 1 && $branches <= 1 && $desired === []) {
            return PlanFeatureCatalog::PLAN_FREE;
        }

        return null;
    }
}
