<?php

namespace App\Observers;

use App\Services\Tenant\TrialOnboardingService;

class TrialOnboardingObserver
{
    public function created(mixed $model): void
    {
        $this->sync();
    }

    public function updated(mixed $model): void
    {
        $this->sync();
    }

    private function sync(): void
    {
        if (! app()->bound(TrialOnboardingService::class)) {
            return;
        }

        app(TrialOnboardingService::class)->syncFromAuth();
    }
}
