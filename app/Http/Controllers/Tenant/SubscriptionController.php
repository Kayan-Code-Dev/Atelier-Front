<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Subscription\RenewSubscriptionRequest;
use App\Http\Requests\Tenant\Subscription\SubmitSubscriptionChangeRequest;
use App\Http\Requests\Tenant\Subscription\UpgradeSubscriptionRequest;
use App\Models\Tenant\User;
use App\Services\Platform\TenantPlanChangeRequestService;
use App\Services\Platform\TenantSubscriptionBillingService;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantQuotaService;
use App\Services\Tenant\TrialOnboardingService;
use App\Support\ApiResponse;
use App\Support\TenantMessages;
use App\Support\TrialOnboarding\TrialOnboardingEventName;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly TenantSubscriptionBillingService $billingService,
        private readonly TenantPlanChangeRequestService $changeRequestService,
        private readonly TenantContext $tenantContext,
        private readonly TenantQuotaService $quotaService,
    ) {}

    public function overview(): JsonResponse
    {
        $tenant = $this->tenantContext->tenant();
        if ($tenant === null) {
            return ApiResponse::error(TenantMessages::CONTEXT_REQUIRED, 400);
        }

        $payload = $this->billingService->overview($tenant);
        $payload['usage'] = $this->quotaService->monthlyUsage($tenant);
        $this->recordTrialSignal(TrialOnboardingEventName::PricingViewed);

        return ApiResponse::success($payload);
    }

    public function featureCatalog(): JsonResponse
    {
        return ApiResponse::success([
            'definitions' => \App\Support\PlanFeatureCatalog::definitions(),
            'plans' => \App\Support\PlanFeatureCatalog::publicPlanSlugs(),
            'comparison' => app(\App\Services\Platform\PlanEntitlementService::class)->comparisonMatrix(),
        ]);
    }

    public function usage(): JsonResponse
    {
        $tenant = $this->tenantContext->tenant();
        if ($tenant === null) {
            return ApiResponse::error(TenantMessages::CONTEXT_REQUIRED, 400);
        }

        return ApiResponse::success($this->quotaService->monthlyUsage($tenant));
    }

    public function paymentGateways(): JsonResponse
    {
        return ApiResponse::success($this->billingService->activePaymentGateways());
    }

    public function renew(RenewSubscriptionRequest $request): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->tenant();
            if ($tenant === null) {
                return ApiResponse::error(TenantMessages::CONTEXT_REQUIRED, 400);
            }

            $subscription = $this->billingService->renew($tenant, $request->validated());

            return ApiResponse::success($subscription, 'تم تجديد الاشتراك');
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function submitChangeRequest(SubmitSubscriptionChangeRequest $request): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->tenant();
            if ($tenant === null) {
                return ApiResponse::error(TenantMessages::CONTEXT_REQUIRED, 400);
            }

            $payload = $request->validated();
            $payload['payment_proof'] = $request->file('payment_proof');

            $result = $this->changeRequestService->submit(
                $tenant,
                $request->user(),
                $payload,
            );
            $this->recordTrialSignal(TrialOnboardingEventName::CheckoutStarted);

            return ApiResponse::success($result, (string) ($result['message'] ?? 'تم إرسال الطلب'), 202);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function upgrade(UpgradeSubscriptionRequest $request): JsonResponse
    {
        return ApiResponse::error(
            'يرجى اختيار الباقة وإتمام الدفع وإرفاق إثبات التحويل لمراجعة الإدارة',
            422,
        );
    }

    private function recordTrialSignal(TrialOnboardingEventName $event): void
    {
        try {
            $user = request()->user();
            if (! $user instanceof User) {
                return;
            }
            app(TrialOnboardingService::class)->recordSignal($user, $event);
        } catch (Throwable) {
            // Commercial intent tracking must never break billing.
        }
    }
}
