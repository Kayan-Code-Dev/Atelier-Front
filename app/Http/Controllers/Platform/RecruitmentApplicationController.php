<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\RecruitmentApplication;
use App\Services\Platform\RecruitmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruitmentApplicationController extends Controller
{
    public function __construct(
        private readonly RecruitmentService $recruitment,
    ) {}

    public function summary(): JsonResponse
    {
        return ApiResponse::success($this->recruitment->applicationSummary());
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 20)));
        $q = RecruitmentApplication::query()->with('job')->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', (string) $request->input('status'));
        }
        if ($request->filled('job_id')) {
            $q->where('job_id', (int) $request->integer('job_id'));
        }
        if ($request->filled('search')) {
            $s = '%'.trim((string) $request->input('search')).'%';
            $q->where(function ($b) use ($s): void {
                $b->where('full_name', 'like', $s)
                    ->orWhere('email', 'like', $s)
                    ->orWhere('phone', 'like', $s)
                    ->orWhere('application_number', 'like', $s)
                    ->orWhere('city', 'like', $s);
            });
        }

        $paginator = $q->paginate($perPage);
        $items = collect($paginator->items())
            ->map(fn (RecruitmentApplication $row) => $this->recruitment->adminApplicationListItem($row))
            ->all();

        return ApiResponse::paginated($paginator, $items);
    }

    public function show(int $id): JsonResponse
    {
        $application = RecruitmentApplication::query()->findOrFail($id);

        return ApiResponse::success($this->recruitment->adminApplicationDetail($application));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(config('recruitment.application_statuses'))],
        ]);

        $application = $this->recruitment->changeApplicationStatus(
            RecruitmentApplication::query()->findOrFail($id),
            $data['status'],
            $request->user()?->id,
        );

        return ApiResponse::success($this->recruitment->adminApplicationDetail($application), 'تم تحديث حالة الطلب');
    }

    public function addNote(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:4000'],
        ]);

        $application = RecruitmentApplication::query()->findOrFail($id);
        $this->recruitment->addNote($application, $data['body'], $request->user()?->id);

        return ApiResponse::success($this->recruitment->adminApplicationDetail($application->fresh()), 'تمت إضافة الملاحظة');
    }

    public function downloadCv(Request $request, int $id): StreamedResponse
    {
        $inline = $request->boolean('inline');

        return $this->recruitment->downloadCv(
            RecruitmentApplication::query()->findOrFail($id),
            $inline,
        );
    }
}
