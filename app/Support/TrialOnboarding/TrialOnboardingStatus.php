<?php

namespace App\Support\TrialOnboarding;

enum TrialOnboardingStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
