<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreRecruitmentJobRequest;
use App\Http\Requests\Platform\UpdateRecruitmentJobRequest;
use App\Models\Central\RecruitmentJob;
use App\Services\Platform\RecruitmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruitmentJobController extends Controller
{
    public function __construct(
        private readonly RecruitmentService $recruitment,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 20)));
        $q = RecruitmentJob::query()->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', (string) $request->input('status'));
        }
        if ($request->filled('search')) {
            $s = '%'.trim((string) $request->input('search')).'%';
            $q->where(function ($b) use ($s): void {
                $b->where('title', 'like', $s)
                    ->orWhere('department', 'like', $s)
                    ->orWhere('slug', 'like', $s)
                    ->orWhere('location', 'like', $s);
            });
        }

        $paginator = $q->paginate($perPage);
        $items = collect($paginator->items())
            ->map(fn (RecruitmentJob $job) => $this->recruitment->adminJobPayload($job))
            ->all();

        return ApiResponse::paginated($paginator, $items);
    }

    public function store(StoreRecruitmentJobRequest $request): JsonResponse
    {
        $job = $this->recruitment->createJob($request->validated());

        return ApiResponse::success($this->recruitment->adminJobPayload($job), 'تم إنشاء الوظيفة', 201);
    }

    public function show(int $id): JsonResponse
    {
        $job = RecruitmentJob::query()->findOrFail($id);

        return ApiResponse::success($this->recruitment->adminJobPayload($job));
    }

    public function update(UpdateRecruitmentJobRequest $request, int $id): JsonResponse
    {
        $job = $this->recruitment->updateJob(RecruitmentJob::query()->findOrFail($id), $request->validated());

        return ApiResponse::success($this->recruitment->adminJobPayload($job), 'تم حفظ الوظيفة');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->recruitment->deleteJob(RecruitmentJob::query()->findOrFail($id));

        return ApiResponse::success(null, 'تم حذف الوظيفة');
    }

    public function publish(int $id): JsonResponse
    {
        $job = $this->recruitment->setJobStatus(RecruitmentJob::query()->findOrFail($id), 'published');

        return ApiResponse::success($this->recruitment->adminJobPayload($job), 'تم نشر الوظيفة');
    }

    public function hide(int $id): JsonResponse
    {
        $job = $this->recruitment->setJobStatus(RecruitmentJob::query()->findOrFail($id), 'draft');

        return ApiResponse::success($this->recruitment->adminJobPayload($job), 'تم إخفاء الوظيفة');
    }

    public function close(int $id): JsonResponse
    {
        $job = $this->recruitment->setJobStatus(RecruitmentJob::query()->findOrFail($id), 'closed');

        return ApiResponse::success($this->recruitment->adminJobPayload($job), 'تم إغلاق الوظيفة');
    }

    public function archive(int $id): JsonResponse
    {
        $job = $this->recruitment->setJobStatus(RecruitmentJob::query()->findOrFail($id), 'archived');

        return ApiResponse::success($this->recruitment->adminJobPayload($job), 'تم أرشفة الوظيفة');
    }
}
