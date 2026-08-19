<?php

namespace Tests\Unit;

use App\Services\Platform\TrialPerformanceScoring;
use Tests\TestCase;

class TrialPerformanceScoringTest extends TestCase
{
    private TrialPerformanceScoring $scoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoring = new TrialPerformanceScoring;
    }

    public function test_health_score_sums_configured_weights_and_explains_why(): void
    {
        $weights = [
            'trial_onboarding_started' => 5,
            'trial_branch_created' => 5,
            'product_created' => 10,
            'trial_reservation_created' => 15,
            'trial_purchase_order_received' => 15,
        ];
        $result = $this->scoring->health(
            $weights,
            [
                'trial_onboarding_started' => true,
                'trial_branch_created' => true,
                'product_created' => false,
                'trial_reservation_created' => true,
                'trial_purchase_order_received' => true,
            ],
            [
                'trial_reservation_created' => 'إنشاء حجز',
                'active_today' => 'نشط اليوم',
            ],
            true,
        );

        $this->assertSame(45, $result['score']);
        $this->assertSame('engaged', $result['band']);
        $this->assertSame('trial_reservation_created', $result['why'][2]['key']);
        $this->assertSame(15, $result['why'][2]['points']);
        $this->assertSame('active_today', $result['why'][4]['key']);
        $this->assertSame(5, $result['why'][4]['points']);
    }

    public function test_health_caps_at_one_hundred(): void
    {
        $result = $this->scoring->health(
            ['a' => 80, 'b' => 30],
            ['a' => true, 'b' => true],
            [],
            true,
        );
        $this->assertSame(100, $result['score']);
        $this->assertSame('highly_engaged', $result['band']);
    }

    public function test_activation_rules_ignore_account_created(): void
    {
        $empty = $this->scoring->activation([
            'branch' => false,
            'cashbox' => false,
            'product' => false,
            'purchase_received' => false,
            'customer' => false,
            'reservation' => false,
            'financial_view' => false,
        ]);
        $this->assertSame('not_activated', $empty['status']);

        $partial = $this->scoring->activation([
            'branch' => true,
            'cashbox' => true,
            'product' => true,
            'purchase_received' => false,
            'customer' => false,
            'reservation' => false,
            'financial_view' => false,
        ]);
        $this->assertSame('partially_activated', $partial['status']);
        $this->assertContains('branch', $partial['met']);
        $this->assertContains('reservation', $partial['missing']);

        $activated = $this->scoring->activation([
            'branch' => true,
            'cashbox' => true,
            'product' => true,
            'purchase_received' => true,
            'customer' => true,
            'reservation' => true,
            'financial_view' => true,
        ]);
        $this->assertSame('activated', $activated['status']);
        $this->assertSame('fully_activated', $this->scoring->fullyActivated('activated', true));
        $this->assertSame('activated', $this->scoring->fullyActivated('activated', false));
    }

    public function test_engagement_uses_rules_not_score_alone(): void
    {
        $this->assertSame('cold', $this->scoring->engagement(false, false, false, 0, 1, 48, 24));
        $this->assertSame('inactive', $this->scoring->engagement(true, true, true, 6, 80, 48, 24));
        $this->assertSame('hot', $this->scoring->engagement(true, true, true, 5, 2, 48, 24));
        $this->assertSame('warm', $this->scoring->engagement(true, true, false, 3, 10, 48, 24));
        $this->assertSame('cold', $this->scoring->engagement(false, true, false, 1, 10, 48, 24));
    }

    public function test_sales_priority_and_lifecycle(): void
    {
        $high = $this->scoring->salesPriority('hot', true, true, 11, true, true);
        $this->assertSame('high', $high['level']);
        $this->assertContains('activated', $high['reasons']);
        $this->assertContains('reservations', $high['reasons']);

        $low = $this->scoring->salesPriority('cold', false, false, 0, false, false);
        $this->assertSame('low', $low['level']);

        $stages = $this->scoring->lifecycle(true, true, true, true, true, false, false);
        $this->assertSame(['created', 'started', 'activated', 'engaged', 'upgrade_intent'], $stages);

        $converted = $this->scoring->lifecycle(true, true, true, true, true, true, true);
        $this->assertContains('converted', $converted);
        $this->assertNotContains('expired', $converted);
    }

    public function test_configured_health_weights_sum_to_one_hundred(): void
    {
        $this->assertSame(100, (int) array_sum((array) config('trial_performance.health_weights')));
    }
}
