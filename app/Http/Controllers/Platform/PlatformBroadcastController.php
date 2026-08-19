<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\PlatformBroadcastResource;
use App\Models\Central\PlatformBroadcast;
use App\Models\Central\SuperAdmin;
use App\Services\Platform\PlatformBroadcastService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformBroadcastController extends Controller
{
    public function __construct(private readonly PlatformBroadcastService $broadcastService) {}

    public function summary(): JsonResponse
    {
        return ApiResponse::success($this->broadcastService->summary());
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        $query = PlatformBroadcast::query()
            ->with('sender')
            ->orderByDesc('sent_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $broadcasts = $query->paginate($perPage);

        return ApiResponse::paginated(
            $broadcasts,
            PlatformBroadcastResource::collection($broadcasts->items())->resolve(),
        );
    }

    public function show(int $id): JsonResponse
    {
        $broadcast = PlatformBroadcast::query()->with('sender')->findOrFail($id);

        return ApiResponse::success(new PlatformBroadcastResource($broadcast));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
            'target_type' => ['required', Rule::in(['all', 'by_plan', 'by_status'])],
            'target_plans' => ['nullable', 'array'],
            'target_plans.*' => ['string', 'max:64'],
            'target_statuses' => ['nullable', 'array'],
            'target_statuses.*' => ['string', 'max:64'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(['inapp', 'email', 'sms'])],
            'priority' => ['nullable', Rule::in(['normal', 'high'])],
        ]);

        if ($data['target_type'] === 'by_plan' && empty($data['target_plans'])) {
            return ApiResponse::error('اختر باقة واحدة على الأقل', 422);
        }

        if ($data['target_type'] === 'by_status' && empty($data['target_statuses'])) {
            return ApiResponse::error('اختر حالة واحدة على الأقل', 422);
        }

        /** @var SuperAdmin $sender */
        $sender = $request->user();

        $broadcast = $this->broadcastService->send($data, $sender);

        return ApiResponse::success(
            new PlatformBroadcastResource($broadcast),
            'تم إرسال الإشعار',
            201,
        );
    }
}
