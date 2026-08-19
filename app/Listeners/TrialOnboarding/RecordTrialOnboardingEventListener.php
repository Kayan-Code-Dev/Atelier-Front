<?php

namespace App\Listeners\TrialOnboarding;

use App\Events\TrialOnboarding\TrialOnboardingProgressed;
use App\Models\Central\TrialOnboardingEvent;

class RecordTrialOnboardingEventListener
{
    public function handle(TrialOnboardingProgressed $event): void
    {
        TrialOnboardingEvent::query()->firstOrCreate(
            [
                'tenant_id' => $event->tenantId,
                'user_id' => $event->userId,
                'event_name' => $event->eventName,
            ],
            [
                'step_key' => $event->stepKey,
                'metadata' => $event->metadata,
                'occurred_at' => now(),
            ],
        );
    }
}
