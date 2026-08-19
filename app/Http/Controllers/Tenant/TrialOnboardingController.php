<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Services\Tenant\TrialOnboardingService;
use App\Support\ApiResponse;
use App\Support\TrialOnboarding\TrialOnboardingEventName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrialOnboardingController extends Controller
{
    public function __construct(private readonly TrialOnboardingService $onboarding) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::unauthorized();
        }

        return ApiResponse::success($this->onboarding->snapshot($user));
    }

    public function start(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::unauthorized();
        }
        if (! $this->onboarding->isEligible()) {
            return ApiResponse::forbidden('Trial onboarding is not available for this account.');
        }

        return ApiResponse::success($this->onboarding->start($user), 'Trial journey started');
    }

    public function view(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::unauthorized();
        }
        if (! $this->onboarding->isEligible()) {
            return ApiResponse::forbidden('Trial onboarding is not available for this account.');
        }

        $step = (string) $request->validate([
            'step' => ['required', 'string', 'max:64'],
        ])['step'];

        return ApiResponse::success($this->onboarding->recordView($user, $step));
    }

    public function signal(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::unauthorized();
        }
        if (! $this->onboarding->isEligible()) {
            return ApiResponse::forbidden('Trial onboarding is not available for this account.');
        }

        $signal = (string) $request->validate([
            'signal' => ['required', 'string', 'in:pricing_viewed,upgrade_clicked,checkout_started'],
        ])['signal'];

        $event = match ($signal) {
            'pricing_viewed' => TrialOnboardingEventName::PricingViewed,
            'upgrade_clicked' => TrialOnboardingEventName::UpgradeClicked,
            default => TrialOnboardingEventName::CheckoutStarted,
        };

        $this->onboarding->recordSignal($user, $event);

        return ApiResponse::success($this->onboarding->snapshot($user));
    }

    public function acknowledge(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::unauthorized();
        }
        if (! $this->onboarding->isEligible()) {
            return ApiResponse::forbidden('Trial onboarding is not available for this account.');
        }

        return ApiResponse::success($this->onboarding->acknowledgeCompletion($user));
    }
}
