<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\EmployeeActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeActivityController extends Controller
{
    public function __construct(private readonly EmployeeActivityLogger $logger) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 30)));
        $rows = $this->logger->paginate([
            'user_id' => $request->query('user_id'),
            'employee_id' => $request->query('employee_id'),
            'module' => $request->query('module'),
            'importance' => $request->query('importance'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'search' => $request->query('search'),
        ], $perPage);

        $data = collect($rows->items())->map(static function ($row): array {
            return [
                'id' => $row->id,
                'at' => $row->created_at?->toIso8601String(),
                'title' => $row->title,
                'description' => $row->description,
                'actor' => $row->actor_name,
                'module' => $row->module,
                'action' => $row->action,
                'importance' => $row->importance,
                'entity_type' => $row->entity_type,
                'entity_id' => $row->entity_id,
                'user_id' => $row->user_id,
                'employee_id' => $row->employee_id,
                'path' => $row->path,
                'method' => $row->method,
            ];
        })->all();

        return ApiResponse::paginated($rows, $data);
    }
}
