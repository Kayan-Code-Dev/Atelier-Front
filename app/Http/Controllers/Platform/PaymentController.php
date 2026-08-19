<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Payment\UpdatePaymentRequest;
use App\Http\Resources\Platform\PaymentResource;
use App\Models\Central\Payment;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        $query = Payment::query()
            ->with(['tenant', 'plan', 'paymentGateway'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('currency')) {
            $query->where('currency', strtoupper((string) $request->query('currency')));
        }

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->query('search')).'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('reference', 'like', $search)
                    ->orWhere('method', 'like', $search)
                    ->orWhereHas('tenant', fn ($q) => $q->where('name', 'like', $search)->orWhere('slug', 'like', $search));
            });
        }

        $payments = $query->paginate($perPage);

        return ApiResponse::paginated(
            $payments,
            PaymentResource::collection($payments->items())->resolve(),
        );
    }

    public function show(int $id): JsonResponse
    {
        $payment = Payment::query()
            ->with(['tenant', 'plan', 'paymentGateway'])
            ->findOrFail($id);

        return ApiResponse::success(new PaymentResource($payment));
    }

    public function update(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        $payment = Payment::query()->findOrFail($id);
        $data = $request->validated();

        if (array_key_exists('amount', $data)) {
            $payment->amount = $data['amount'];
        }
        if (array_key_exists('reference', $data)) {
            $payment->reference = $data['reference'];
        }
        if (array_key_exists('notes', $data)) {
            $payment->notes = $data['notes'];
        }
        if (array_key_exists('method', $data)) {
            $payment->method = $data['method'];
        }

        if (array_key_exists('status', $data)) {
            $status = (string) $data['status'];
            $payment->status = $status;

            if ($status === 'paid') {
                $payment->paid_at = $payment->paid_at ?? CarbonImmutable::now();
            } else {
                $payment->paid_at = null;
            }
        }

        $payment->save();
        $payment->load(['tenant', 'plan', 'paymentGateway']);

        return ApiResponse::success(new PaymentResource($payment), 'تم تحديث الدفعة بنجاح');
    }

    public function destroy(int $id): JsonResponse
    {
        $payment = Payment::query()->findOrFail($id);
        $payment->delete();

        return ApiResponse::success(null, 'تم حذف الدفعة بنجاح');
    }
}
