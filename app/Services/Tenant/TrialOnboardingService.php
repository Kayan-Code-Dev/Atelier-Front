<?php

namespace App\Services\Tenant;

use App\Events\TrialOnboarding\TrialOnboardingProgressed;
use App\Listeners\TrialOnboarding\RecordTrialOnboardingEventListener;
use App\Models\Central\Tenant;
use App\Models\Tenant\TrialOnboardingState;
use App\Models\Tenant\User;
use App\Support\TrialOnboarding\TrialOnboardingCatalog;
use App\Support\TrialOnboarding\TrialOnboardingEventName;
use App\Support\TrialOnboarding\TrialOnboardingStatus;
use App\Support\TrialOnboarding\TrialOnboardingStepKey;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TrialOnboardingService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrialOnboardingEvaluator $evaluator,
        private readonly RecordTrialOnboardingEventListener $eventRecorder,
    ) {}

    public function isEligible(?Tenant $tenant = null): bool
    {
        $tenant ??= $this->tenantContext->tenant();

        return $tenant !== null && $tenant->isDemo();
    }

    public function syncFromAuth(): void
    {
        try {
            $tenant = $this->tenantContext->tenant();
            $user = Auth::user();
            if (! $this->isEligible($tenant) || ! $user instanceof User) {
                return;
            }
            $this->sync($tenant, $user);
        } catch (Throwable) {
            // Never interrupt business CRUD if onboarding tracking fails.
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user): array
    {
        $tenant = $this->tenantContext->requireTenant();
        if (! $this->isEligible($tenant)) {
            return $this->ineligiblePayload();
        }

        $state = $this->sync($tenant, $user);

        return $this->present($tenant, $user, $state);
    }

    /**
     * @return array<string, mixed>
     */
    public function start(User $user): array
    {
        $tenant = $this->tenantContext->requireTenant();
        if (! $this->isEligible($tenant)) {
            return $this->ineligiblePayload();
        }

        $state = $this->stateFor($user);
        if ($state->started_at === null) {
            $state->started_at = now();
        }
        if ($state->status === TrialOnboardingStatus::NotStarted->value) {
            $state->status = TrialOnboardingStatus::InProgress->value;
        }
        $state->last_activity_at = now();
        $state->save();

        $this->recordEvent(
            $tenant,
            $user,
            TrialOnboardingEventName::Started->value,
            null,
            ['source' => 'trial', 'step' => 'started'],
        );

        $state = $this->sync($tenant, $user);

        return $this->present($tenant, $user, $state);
    }

    /**
     * @return array<string, mixed>
     */
    public function recordView(User $user, string $stepKey): array
    {
        $tenant = $this->tenantContext->requireTenant();
        if (! $this->isEligible($tenant)) {
            return $this->ineligiblePayload();
        }

        $step = TrialOnboardingStepKey::tryFrom($stepKey);
        if ($step === null || ! $step->isViewStep()) {
            return $this->snapshot($user);
        }

        $state = $this->stateFor($user);
        $viewed = $state->viewedStepKeys();
        if (! in_array($step->value, $viewed, true)) {
            $viewed[] = $step->value;
            $state->viewed_steps = $viewed;
            $state->last_activity_at = now();
            if ($state->status === TrialOnboardingStatus::NotStarted->value) {
                $state->status = TrialOnboardingStatus::InProgress->value;
                $state->started_at = $state->started_at ?? now();
            }
            $state->save();
        }

        $state = $this->sync($tenant, $user);

        return $this->present($tenant, $user, $state);
    }

    /**
     * @return array<string, mixed>
     */
    public function acknowledgeCompletion(User $user): array
    {
        $tenant = $this->tenantContext->requireTenant();
        if (! $this->isEligible($tenant)) {
            return $this->ineligiblePayload();
        }

        $state = $this->stateFor($user);
        if ($state->status === TrialOnboardingStatus::Completed->value && $state->completion_acknowledged_at === null) {
            $state->completion_acknowledged_at = now();
            $state->last_activity_at = now();
            $state->save();
        }

        return $this->present($tenant, $user, $state);
    }

    public function sync(Tenant $tenant, User $user): TrialOnboardingState
    {
        $state = $this->stateFor($user);
        $facts = $this->evaluator->evaluate($state->viewedStepKeys());
        $completed = [];
        foreach (TrialOnboardingStepKey::ordered() as $step) {
            if ($facts[$step->value] ?? false) {
                $completed[] = $step->value;
            }
        }

        $previous = is_array($state->completed_steps) ? $state->completed_steps : [];
        $newlyCompleted = array_values(array_diff($completed, $previous));

        $current = $this->firstIncomplete($completed);
        $allDone = $current === null;
        $wasStarted = $state->started_at !== null
            || $state->status !== TrialOnboardingStatus::NotStarted->value
            || $completed !== [];

        if ($allDone && $wasStarted) {
            $state->status = TrialOnboardingStatus::Completed->value;
            $state->completed_at = $state->completed_at ?? now();
            $state->current_step = TrialOnboardingStepKey::AccountStatement->value;
        } elseif ($completed !== [] || $state->started_at !== null) {
            if ($state->status !== TrialOnboardingStatus::Completed->value) {
                $state->status = TrialOnboardingStatus::InProgress->value;
            }
            $state->current_step = $current?->value;
            if ($state->started_at === null && $completed !== []) {
                $state->started_at = now();
            }
        } else {
            $state->status = TrialOnboardingStatus::NotStarted->value;
            $state->current_step = TrialOnboardingStepKey::BranchSetup->value;
        }

        $state->completed_steps = $completed;
        $state->last_activity_at = now();
        $state->save();

        foreach ($newlyCompleted as $stepKey) {
            $step = TrialOnboardingStepKey::from($stepKey);
            $this->recordEvent(
                $tenant,
                $user,
                $step->event()->value,
                $step->value,
                $this->safeMetadata($step, $facts),
            );
        }

        if ($allDone && $wasStarted) {
            $this->recordEvent(
                $tenant,
                $user,
                TrialOnboardingEventName::Completed->value,
                TrialOnboardingStepKey::AccountStatement->value,
                ['source' => 'trial', 'step' => 'completed'],
            );
        }

        return $state->refresh();
    }

    private function stateFor(User $user): TrialOnboardingState
    {
        return TrialOnboardingState::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => TrialOnboardingStatus::NotStarted->value,
                'current_step' => TrialOnboardingStepKey::BranchSetup->value,
                'completed_steps' => [],
                'viewed_steps' => [],
            ],
        );
    }

    /**
     * @param  list<string>  $completed
     */
    private function firstIncomplete(array $completed): ?TrialOnboardingStepKey
    {
        foreach (TrialOnboardingStepKey::ordered() as $step) {
            if (! in_array($step->value, $completed, true)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @param  array<string, bool>  $facts
     * @return array<string, mixed>
     */
    private function safeMetadata(TrialOnboardingStepKey $step, array $facts): array
    {
        $meta = [
            'source' => 'trial',
            'step' => $step->value,
        ];
        if ($step === TrialOnboardingStepKey::InventoryVerified) {
            $meta['item_count'] = $this->evaluator->summaryCounts()['inventory_items'];
        }
        if ($step === TrialOnboardingStepKey::PurchaseOrderReceived) {
            $meta['received'] = (bool) ($facts[$step->value] ?? false);
        }

        return $meta;
    }

    /**
     * First-occurrence commercial or journey signal. Safe no-op for paid tenants.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordSignal(
        User $user,
        TrialOnboardingEventName $event,
        ?string $stepKey = null,
        array $metadata = [],
    ): void {
        $tenant = $this->tenantContext->tenant();
        if ($tenant === null || ! $this->isEligible($tenant)) {
            return;
        }

        $this->recordEvent(
            $tenant,
            $user,
            $event->value,
            $stepKey,
            $metadata === [] ? ['source' => 'trial', 'signal' => $event->value] : $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordEvent(
        Tenant $tenant,
        User $user,
        string $eventName,
        ?string $stepKey,
        array $metadata,
    ): void {
        $event = new TrialOnboardingProgressed(
            tenantId: (int) $tenant->id,
            userId: (int) $user->id,
            eventName: $eventName,
            stepKey: $stepKey,
            metadata: $metadata,
        );

        try {
            if (app()->bound(EventBusInterface::class)) {
                app(EventBusInterface::class)->publish($event);
            } else {
                $this->eventRecorder->handle($event);
            }
        } catch (Throwable) {
            $this->eventRecorder->handle($event);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Tenant $tenant, User $user, TrialOnboardingState $state): array
    {
        $completed = is_array($state->completed_steps) ? array_values($state->completed_steps) : [];
        $current = TrialOnboardingStepKey::tryFrom((string) $state->current_step)
            ?? $this->firstIncomplete($completed)
            ?? TrialOnboardingStepKey::BranchSetup;
        $total = count(TrialOnboardingStepKey::cases());
        $doneCount = count($completed);
        $next = $this->firstIncomplete($completed);

        $steps = [];
        foreach (TrialOnboardingCatalog::contracts() as $contract) {
            $key = (string) $contract['key'];
            $isDone = in_array($key, $completed, true);
            $steps[] = array_merge($contract, [
                'completed' => $isDone,
                'current' => ! $isDone && $next?->value === $key,
            ]);
        }

        return [
            'eligible' => true,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => $state->status,
            'current_step' => $current->value,
            'completed_steps' => $completed,
            'started_at' => $state->started_at?->toIso8601String(),
            'completed_at' => $state->completed_at?->toIso8601String(),
            'last_activity_at' => $state->last_activity_at?->toIso8601String(),
            'completion_acknowledged' => $state->completion_acknowledged_at !== null,
            'progress' => [
                'completed' => $doneCount,
                'total' => $total,
                'percent' => $total > 0 ? (int) round(($doneCount / $total) * 100) : 0,
            ],
            'next_step' => $next ? TrialOnboardingCatalog::contract($next) : null,
            'steps' => $steps,
            'summary' => $this->evaluator->summaryCounts(),
            'resume' => $state->status === TrialOnboardingStatus::InProgress->value && $doneCount > 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ineligiblePayload(): array
    {
        return [
            'eligible' => false,
            'status' => TrialOnboardingStatus::NotStarted->value,
            'steps' => [],
            'progress' => ['completed' => 0, 'total' => 0, 'percent' => 0],
            'summary' => null,
        ];
    }
}
