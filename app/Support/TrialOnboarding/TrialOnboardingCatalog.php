<?php

namespace App\Support\TrialOnboarding;

final class TrialOnboardingCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function contracts(): array
    {
        return array_map(
            fn (TrialOnboardingStepKey $step): array => self::contract($step),
            TrialOnboardingStepKey::ordered(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function contract(TrialOnboardingStepKey $step): array
    {
        return [
            'key' => $step->value,
            'order' => $step->order(),
            'title' => $step->title(),
            'description' => $step->description(),
            'required_action' => $step->requiredAction(),
            'completion_condition' => $step->completionCondition(),
            'route' => $step->route(),
            'event' => $step->event()->value,
            'target' => $step->target(),
            'success_copy' => $step->successCopy(),
            'metadata' => [
                'source' => 'trial',
                'view_step' => $step->isViewStep(),
            ],
        ];
    }
}
