<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Hr\Employee\StoreHrEmployeeNoteRequest;
use App\Http\Resources\Tenant\HrEmployeeNoteResource;
use App\Services\Tenant\EmployeeActivityLogger;
use App\Services\Tenant\HrEmployeeNoteService;
use App\Services\Tenant\HrEmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrEmployeeNoteController extends Controller
{
    public function __construct(
        private readonly HrEmployeeService $hrEmployeeService,
        private readonly HrEmployeeNoteService $noteService,
        private readonly EmployeeActivityLogger $activityLogger,
    ) {}

    public function index(Request $request, int $employee): JsonResponse
    {
        $this->hrEmployeeService->findOrFail($employee);
        $perPage = max(1, min(100, $request->integer('per_page', 30)));
        $notes = $this->noteService->paginate($employee, $perPage);

        return ApiResponse::paginated($notes, HrEmployeeNoteResource::collection($notes->items())->resolve());
    }

    public function store(StoreHrEmployeeNoteRequest $request, int $employee): JsonResponse
    {
        $employeeModel = $this->hrEmployeeService->findOrFail($employee);
        $note = $this->noteService->create($employeeModel, $request->validated(), $request->user());

        $this->activityLogger->log([
            'user_id' => $request->user()?->id,
            'employee_id' => $employeeModel->id,
            'actor_name' => $request->user()?->name ?? $request->user()?->email,
            'module' => 'employees',
            'action' => 'note_created',
            'title' => 'إضافة ملاحظة موظف',
            'description' => mb_substr((string) $note->content, 0, 180),
            'entity_type' => 'hr_employee',
            'entity_id' => $employeeModel->id,
            'importance' => $note->type === 'warning' ? 'important' : 'normal',
        ]);

        return ApiResponse::success(new HrEmployeeNoteResource($note), 'تم حفظ الملاحظة', 201);
    }

    public function destroy(Request $request, int $employee, int $note): JsonResponse
    {
        $this->hrEmployeeService->findOrFail($employee);
        $model = $this->noteService->findForEmployee($employee, $note);
        $this->noteService->delete($model);

        return ApiResponse::success(null, 'تم حذف الملاحظة');
    }
}
