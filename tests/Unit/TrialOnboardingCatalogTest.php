<?php

namespace Tests\Unit;

use App\Support\TrialOnboarding\TrialOnboardingCatalog;
use App\Support\TrialOnboarding\TrialOnboardingEventName;
use App\Support\TrialOnboarding\TrialOnboardingStepKey;
use Tests\TestCase;

class TrialOnboardingCatalogTest extends TestCase
{
    public function test_journey_has_ten_ordered_steps_with_contracts(): void
    {
        $steps = TrialOnboardingStepKey::ordered();
        $this->assertCount(10, $steps);
        $this->assertSame('branch_setup', $steps[0]->value);
        $this->assertSame('account_statement', $steps[9]->value);

        $contracts = TrialOnboardingCatalog::contracts();
        $this->assertCount(10, $contracts);
        foreach ($contracts as $index => $contract) {
            $this->assertSame($index + 1, $contract['order']);
            $this->assertNotEmpty($contract['key']);
            $this->assertNotEmpty($contract['title']);
            $this->assertNotEmpty($contract['description']);
            $this->assertNotEmpty($contract['required_action']);
            $this->assertNotEmpty($contract['completion_condition']);
            $this->assertNotEmpty($contract['route']);
            $this->assertNotEmpty($contract['event']);
            $this->assertStringStartsWith('trial_', (string) $contract['event']);
        }
    }

    public function test_view_steps_are_only_balances_and_statement(): void
    {
        $viewSteps = array_filter(
            TrialOnboardingStepKey::cases(),
            fn (TrialOnboardingStepKey $step): bool => $step->isViewStep(),
        );
        $this->assertCount(2, $viewSteps);
        $this->assertContains(TrialOnboardingStepKey::BalancesReview, $viewSteps);
        $this->assertContains(TrialOnboardingStepKey::AccountStatement, $viewSteps);
    }

    public function test_completion_events_cover_the_full_funnel(): void
    {
        $names = array_map(fn (TrialOnboardingEventName $event): string => $event->value, TrialOnboardingEventName::cases());
        $this->assertContains('trial_onboarding_started', $names);
        $this->assertContains('trial_onboarding_completed', $names);
        $this->assertContains('trial_branch_created', $names);
        $this->assertContains('trial_purchase_order_received', $names);
        $this->assertContains('trial_inventory_verified', $names);
        $this->assertContains('trial_pricing_viewed', $names);
        $this->assertContains('trial_upgrade_clicked', $names);
        $this->assertContains('trial_checkout_started', $names);
    }
}
