<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Payment;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use App\Support\ApiResponse;
use App\Support\PlanCurrency;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $now = CarbonImmutable::now();
        $monthStart = $now->startOfMonth();
        $prevMonthStart = $monthStart->subMonth();
        $prevMonthEnd = $monthStart->subSecond();

        $activeSubscriptions = Subscription::query()->where('status', 'active')->count();
        $newAteliers = Tenant::query()->where('created_at', '>=', $now->subDays(30))->count();
        $cancelled = Subscription::query()
            ->where('status', 'cancelled')
            ->where('updated_at', '>=', $now->subDays(30))
            ->count();
        $churnRate = $activeSubscriptions > 0
            ? round(($cancelled / max(1, $activeSubscriptions + $cancelled)) * 100, 1)
            : 0.0;

        $revenueByCurrency = Payment::query()
            ->select('currency', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'paid')
            ->groupBy('currency')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row): array {
                $currency = PlanCurrency::normalize($row->currency ?? 'EGP');

                return [
                    'currency' => $currency,
                    'currency_symbol' => PlanCurrency::symbol($currency),
                    'total' => round((float) $row->total, 2),
                    'count' => (int) $row->count,
                ];
            })
            ->values()
            ->all();

        $primary = $revenueByCurrency[0] ?? [
            'currency' => 'EGP',
            'currency_symbol' => PlanCurrency::symbol('EGP'),
            'total' => 0,
            'count' => 0,
        ];

        $thisMonth = (float) Payment::query()
            ->where('status', 'paid')
            ->where('currency', $primary['currency'])
            ->where('paid_at', '>=', $monthStart)
            ->sum('amount');
        $prevMonth = (float) Payment::query()
            ->where('status', 'paid')
            ->where('currency', $primary['currency'])
            ->whereBetween('paid_at', [$prevMonthStart, $prevMonthEnd])
            ->sum('amount');

        $revenueChange = $prevMonth > 0
            ? round((($thisMonth - $prevMonth) / $prevMonth) * 100, 1)
            : ($thisMonth > 0 ? 100.0 : 0.0);

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = $now->startOfMonth()->subMonths($i);
            $end = $start->endOfMonth();
            $label = $start->format('M');
            $byCurrency = Payment::query()
                ->select('currency', DB::raw('SUM(amount) as total'))
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$start, $end])
                ->groupBy('currency')
                ->get()
                ->mapWithKeys(fn ($r) => [PlanCurrency::normalize($r->currency ?? 'EGP') => round((float) $r->total, 2)])
                ->all();

            $months[] = [
                'month' => $label,
                'totals' => $byCurrency,
                'primary_total' => (float) ($byCurrency[$primary['currency']] ?? 0),
            ];
        }

        $growth = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = $now->startOfMonth()->subMonths($i);
            $end = $start->endOfMonth();
            $growth[] = [
                'month' => $start->format('M'),
                'subscriptions' => Subscription::query()
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
                'ateliers' => Tenant::query()
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ];
        }

        $recentSubscriptions = Subscription::query()
            ->with(['plan', 'tenant'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (Subscription $sub): array {
                $currency = PlanCurrency::normalize($sub->plan?->currency ?? 'EGP');

                return [
                    'id' => $sub->id,
                    'tenant_name' => $sub->tenant?->name ?? '—',
                    'plan_name' => $sub->plan?->name ?? '—',
                    'price' => number_format((float) ($sub->plan?->price ?? 0), 2, '.', ''),
                    'currency' => $currency,
                    'currency_symbol' => PlanCurrency::symbol($currency),
                    'status' => $sub->status,
                    'created_at' => $sub->created_at?->toISOString(),
                ];
            })
            ->all();

        $recentPayments = Payment::query()
            ->with(['tenant', 'plan'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(function (Payment $payment): array {
                $currency = PlanCurrency::normalize($payment->currency ?? $payment->plan?->currency ?? 'EGP');

                return [
                    'id' => $payment->id,
                    'tenant_name' => $payment->tenant?->name ?? '—',
                    'plan_name' => $payment->plan?->name ?? '—',
                    'amount' => number_format((float) $payment->amount, 2, '.', ''),
                    'currency' => $currency,
                    'currency_symbol' => PlanCurrency::symbol($currency),
                    'status' => $payment->status,
                    'paid_at' => $payment->paid_at?->toISOString(),
                ];
            })
            ->all();

        return ApiResponse::success([
            'stats' => [
                'total_revenue' => [
                    'value' => $primary['total'],
                    'currency' => $primary['currency'],
                    'currency_symbol' => $primary['currency_symbol'],
                    'change' => $revenueChange,
                    'by_currency' => $revenueByCurrency,
                ],
                'active_subscriptions' => [
                    'value' => $activeSubscriptions,
                    'change' => null,
                ],
                'new_ateliers' => [
                    'value' => $newAteliers,
                    'change' => null,
                ],
                'churn_rate' => [
                    'value' => $churnRate,
                    'change' => null,
                ],
            ],
            'revenue_chart' => $months,
            'growth_chart' => $growth,
            'recent_subscriptions' => $recentSubscriptions,
            'recent_payments' => $recentPayments,
        ]);
    }
}
