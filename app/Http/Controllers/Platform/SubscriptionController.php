<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Subscription\RenewSubscriptionRequest;
use App\Http\Requests\Platform\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\Platform\SubscriptionResource;
use App\Models\Central\Subscription;
use App\Services\Platform\SubscriptionAdminService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionAdminService $subscriptionAdminService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $subscriptions = $this->subscriptionAdminService->paginate([
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ], $perPage);

        return ApiResponse::paginated(
            $subscriptions,
            SubscriptionResource::collection($subscriptions->items())->resolve(),
        );
    }

    public function show(int $id): JsonResponse
    {
        $subscription = Subscription::with(['plan', 'tenant', 'payments.plan', 'payments.paymentGateway'])
            ->findOrFail($id);

        return ApiResponse::success(new SubscriptionResource($subscription));
    }

    public function update(UpdateSubscriptionRequest $request, int $id): JsonResponse
    {
        $subscription = Subscription::query()->findOrFail($id);

        try {
            $subscription = $this->subscriptionAdminService->update($subscription, $request->validated());
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            new SubscriptionResource($subscription),
            'تم تحديث الاشتراك بنجاح',
        );
    }

    public function renew(RenewSubscriptionRequest $request, int $id): JsonResponse
    {
        $subscription = Subscription::query()->findOrFail($id);

        try {
            $subscription = $this->subscriptionAdminService->renew($subscription, $request->validated());
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            new SubscriptionResource($subscription),
            'تم تجديد الاشتراك بنجاح',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $subscription = Subscription::query()->findOrFail($id);
        $this->subscriptionAdminService->destroy($subscription);

        return ApiResponse::success(null, 'تم حذف الاشتراك بنجاح');
    }
}
