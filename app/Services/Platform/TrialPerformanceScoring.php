<?php

namespace App\Services\Platform;

final class TrialPerformanceScoring
{
    /**
     * @param  array<string, int>  $weights
     * @param  array<string, bool>  $signals
     * @param  array<string, string>  $labels
     * @return array{score:int,band:string,why:list<array{key:string,label:string,points:int}>}
     */
    public function health(array $weights, array $signals, array $labels, bool $activeToday = false): array
    {
        $why = [];
        $score = 0;
        foreach ($weights as $key => $points) {
            if (! ($signals[$key] ?? false)) {
                continue;
            }
            $points = (int) $points;
            $score += $points;
            $why[] = [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'points' => $points,
            ];
        }
        if ($activeToday) {
            $bonus = 5;
            $score += $bonus;
            $why[] = [
                'key' => 'active_today',
                'label' => $labels['active_today'] ?? 'نشط اليوم',
                'points' => $bonus,
            ];
        }
        $score = min(100, $score);

        return [
            'score' => $score,
            'band' => $this->healthBand($score),
            'why' => $why,
        ];
    }

    public function healthBand(int $score): string
    {
        return match (true) {
            $score >= 75 => 'highly_engaged',
            $score >= 45 => 'engaged',
            $score >= 20 => 'exploring',
            default => 'idle',
        };
    }

    /**
     * @param  array<string, bool>  $requirements
     * @return array{status:string,met:list<string>,missing:list<string>}
     */
    public function activation(array $requirements): array
    {
        $met = [];
        $missing = [];
        foreach ($requirements as $key => $done) {
            if ($done) {
                $met[] = $key;
            } else {
                $missing[] = $key;
            }
        }
        $total = count($requirements);
        $doneCount = count($met);
        $status = match (true) {
            $doneCount === 0 => 'not_activated',
            $doneCount === $total => 'activated',
            default => 'partially_activated',
        };

        return [
            'status' => $status,
            'met' => $met,
            'missing' => $missing,
        ];
    }

    public function fullyActivated(string $activationStatus, bool $onboardingCompleted): string
    {
        if ($activationStatus === 'activated' && $onboardingCompleted) {
            return 'fully_activated';
        }

        return $activationStatus;
    }

    /**
     * @return 'hot'|'warm'|'cold'|'inactive'
     */
    public function engagement(
        bool $activated,
        bool $usedSystem,
        bool $financialsReached,
        int $coreActions,
        ?int $hoursSinceActivity,
        int $inactiveAfterHours,
        int $hotRecentHours,
    ): string {
        if ($hoursSinceActivity === null || $hoursSinceActivity >= $inactiveAfterHours) {
            return $usedSystem ? 'inactive' : 'cold';
        }
        if (! $usedSystem) {
            return 'cold';
        }
        $recent = $hoursSinceActivity <= $hotRecentHours;
        if ($activated && $recent && $coreActions >= 4 && $financialsReached) {
            return 'hot';
        }
        if ($activated || $coreActions >= 3) {
            return 'warm';
        }

        return 'cold';
    }

    /**
     * @param  list<string>  $reasons
     * @return array{level:string,reasons:list<string>}
     */
    public function salesPriority(
        string $engagement,
        bool $activated,
        bool $activeToday,
        int $reservations,
        bool $financialsReached,
        bool $upgradeIntent,
    ): array {
        $reasons = [];
        if ($activated) {
            $reasons[] = 'activated';
        }
        if ($activeToday) {
            $reasons[] = 'active_today';
        }
        if ($reservations > 0) {
            $reasons[] = 'reservations';
        }
        if ($financialsReached) {
            $reasons[] = 'financials_viewed';
        }
        if ($upgradeIntent) {
            $reasons[] = 'upgrade_intent';
        }

        $level = match (true) {
            $engagement === 'hot' && $activated => 'high',
            $activated && ($activeToday || $financialsReached || $upgradeIntent) => 'high',
            $engagement === 'warm' || $activated => 'medium',
            default => 'low',
        };

        return ['level' => $level, 'reasons' => $reasons];
    }

    /**
     * @return list<string>
     */
    public function lifecycle(
        bool $created,
        bool $started,
        bool $activated,
        bool $engaged,
        bool $upgradeIntent,
        bool $converted,
        bool $expired,
    ): array {
        $stages = ['created'];
        if ($started) {
            $stages[] = 'started';
        }
        if ($activated) {
            $stages[] = 'activated';
        }
        if ($engaged) {
            $stages[] = 'engaged';
        }
        if ($upgradeIntent) {
            $stages[] = 'upgrade_intent';
        }
        if ($converted) {
            $stages[] = 'converted';
        } elseif ($expired) {
            $stages[] = 'expired';
        }

        return $created ? $stages : [];
    }
}
