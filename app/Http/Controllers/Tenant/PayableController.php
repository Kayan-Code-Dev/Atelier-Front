<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\PayableSubledgerService;
use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use App\Support\Reports\TabularExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayableController extends Controller
{
    public function __construct(
        private readonly PayableSubledgerService $payables,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->payables->index($this->filters($request)));
    }

    public function show(Request $request, int $supplier): JsonResponse
    {
        return ApiResponse::success($this->payables->statement($supplier, $this->filters($request)));
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $data = $this->payables->index($this->filters($request));
        $rows = array_map(static fn (array $row): array => [
            $row['name'],
            $row['billed'],
            $row['paid'],
            $row['outstanding'],
            $row['overdue'],
            $row['aging']['current'],
            $row['aging']['1_30'],
            $row['aging']['31_60'],
            $row['aging']['61_90'],
            $row['aging']['90_plus'],
        ], $data['suppliers']);

        return TabularExport::download(
            $request->query('format'),
            'payables',
            'ذمم الموردين',
            ['المورد', 'الفواتير', 'المدفوع', 'المستحق', 'متأخر', 'حالي', '1-30', '31-60', '61-90', '90+'],
            $rows,
            ['المنشأة' => $this->tenantContext->tenant()?->name ?? '']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'branch_id' => $request->query('branch_id'),
            'user' => $request->user(),
        ];
    }
}
