<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

/**
 * Score is signal-based, not message-count-only.
 *
 * @phpstan-type Signal array{key: string, points: int, label: string}
 */
final class AiSalesLeadScorer
{
    /**
     * @param  array<string, mixed>  $signals
     * @return array{value: int, max: 100, band: 'cold'|'warm'|'hot', reasons: list<string>, signals: list<Signal>, inputs: array<string, mixed>}
     */
    public function score(array $signals): array
    {
        $detected = [];
        $add = function (string $key, int $points, string $label, bool $hit) use (&$detected): void {
            if ($hit) {
                $detected[] = ['key' => $key, 'points' => $points, 'label' => $label];
            }
        };

        $add('asked_price', 12, 'Asked about price', (bool) ($signals['asked_price'] ?? false));
        $add('asked_plans', 10, 'Asked about plans', (bool) ($signals['asked_plans'] ?? false));
        $add('asked_payment', 15, 'Asked about payment', (bool) ($signals['asked_payment'] ?? false));
        $add('asked_demo', 18, 'Asked about demo', (bool) ($signals['asked_demo'] ?? false));
        $add('mentioned_branches', 8, 'Mentioned branch count', (bool) ($signals['mentioned_branches'] ?? false) || (int) ($signals['branch_count'] ?? 0) > 0);
        $add('mentioned_employees', 6, 'Mentioned employee count', (bool) ($signals['mentioned_employees'] ?? false) || (int) ($signals['user_count'] ?? 0) > 0);
        $add('mentioned_software', 8, 'Mentioned current software', (bool) ($signals['mentioned_software'] ?? false) || $this->present($signals['current_software'] ?? null));
        $add('asked_switching', 10, 'Asked about switching', (bool) ($signals['asked_switching'] ?? false));
        $add('requested_trial', 20, 'Requested trial', (bool) ($signals['requested_trial'] ?? false));
        $add('requested_purchase', 25, 'Requested purchase', (bool) ($signals['requested_purchase'] ?? false));
        $add('feature_requirement', 8, 'Named a required feature', (bool) ($signals['feature_requirement'] ?? false) || (is_array($signals['desired_features'] ?? null) && $signals['desired_features'] !== []));
        $add('objection', 4, 'Raised an objection', (bool) ($signals['objection'] ?? false));

        $branches = (int) ($signals['branch_count'] ?? 0);
        if ($branches >= 2) {
            $detected[] = ['key' => 'multi_branch', 'points' => 10, 'label' => $branches.' branches'];
        }
        $users = (int) ($signals['user_count'] ?? 0);
        if ($users >= 5) {
            $detected[] = ['key' => 'team_size', 'points' => 8, 'label' => $users.' employees'];
        }

        $engagement = max(0, (int) ($signals['engagement_count'] ?? 0));
        if ($engagement > 1) {
            $repeat = min(15, ($engagement - 1) * 5);
            $detected[] = ['key' => 'repeated_engagement', 'points' => $repeat, 'label' => 'Repeated engagement'];
        }

        $value = 0;
        foreach ($detected as $row) {
            $value += $row['points'];
        }
        $value = max(0, min(100, $value));
        $band = $value >= 70 ? 'hot' : ($value >= 40 ? 'warm' : 'cold');

        return [
            'value' => $value,
            'max' => 100,
            'band' => $band,
            'reasons' => array_map(static fn (array $row): string => '+ '.$row['label'], $detected),
            'signals' => $detected,
            'inputs' => $signals,
        ];
    }

    private function present(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
