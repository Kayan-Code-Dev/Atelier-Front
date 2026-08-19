<?php

namespace App\Services\Tenant;

use App\Models\Tenant\EmployeeActivityLog;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeActivityLogger
{
    public function __construct(private readonly TenantNotifier $notifier) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(array $payload): ?EmployeeActivityLog
    {
        try {
            $row = EmployeeActivityLog::query()->create([
                'user_id' => $payload['user_id'] ?? null,
                'employee_id' => $payload['employee_id'] ?? null,
                'actor_name' => $payload['actor_name'] ?? null,
                'module' => $payload['module'] ?? 'system',
                'action' => $payload['action'] ?? 'unknown',
                'method' => $payload['method'] ?? null,
                'path' => $payload['path'] ?? null,
                'title' => $payload['title'] ?? 'نشاط',
                'description' => $payload['description'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'entity_id' => $payload['entity_id'] ?? null,
                'importance' => $payload['importance'] ?? 'normal',
                'meta' => $payload['meta'] ?? null,
                'ip_address' => $payload['ip_address'] ?? null,
                'user_agent' => $payload['user_agent'] ?? null,
                'created_at' => now(),
            ]);

            $importance = (string) ($payload['importance'] ?? 'normal');
            if (in_array($importance, ['important', 'critical'], true)) {
                $this->notifyImportant($row);
            }

            return $row;
        } catch (Throwable $e) {
            Log::warning('Failed to write employee activity log', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function logFromRequest(Request $request, int $statusCode): void
    {
        if ($statusCode >= 400) {
            return;
        }

        $method = strtoupper($request->method());
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $path = '/'.ltrim($request->path(), '/');
        // Only tenant API mutations
        if (! str_contains($path, '/api/tenant/')) {
            return;
        }

        // Skip noisy endpoints
        if (str_contains($path, '/notifications') || str_contains($path, '/intelligence/') || str_contains($path, '/logout') || str_contains($path, '/trial-onboarding')) {
            return;
        }

        $mapped = $this->mapRequest($method, $path, $request);
        if ($mapped === null) {
            return;
        }

        $user = $request->user();
        $userId = $user instanceof User ? $user->id : null;
        $actorName = $user instanceof User ? ($user->name ?? $user->email ?? 'مستخدم') : 'نظام';
        $employeeId = $request->integer('employee_id') ?: null;
        if (! $employeeId && preg_match('#/employees/(\d+)#', $path, $matches) === 1) {
            $employeeId = (int) $matches[1];
        }
        if (! $employeeId && $userId) {
            $employeeId = HrEmployee::query()->where('user_id', $userId)->value('id');
        }

        $this->log([
            'user_id' => $userId,
            'employee_id' => $employeeId,
            'actor_name' => $actorName,
            'module' => $mapped['module'],
            'action' => $mapped['action'],
            'method' => $method,
            'path' => $path,
            'title' => $mapped['title'],
            'description' => $mapped['description'],
            'entity_type' => $mapped['entity_type'] ?? null,
            'entity_id' => $mapped['entity_id'] ?? null,
            'importance' => $mapped['importance'],
            'meta' => [
                'status' => $statusCode,
                'route' => optional($request->route())->getName(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        $query = EmployeeActivityLog::query()->latest('id');
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentForDashboard(?int $branchId = null, int $limit = 25): array
    {
        $query = EmployeeActivityLog::query()->latest('id')->limit($limit);

        if ($branchId !== null) {
            $employeeIds = HrEmployee::query()->where('branch_id', $branchId)->pluck('id');
            $query->where(function (Builder $builder) use ($employeeIds): void {
                $builder->whereIn('employee_id', $employeeIds)
                    ->orWhereNull('employee_id');
            });
        }

        return $query->get()->map(static function (EmployeeActivityLog $row): array {
            return [
                'id' => $row->id,
                'at' => $row->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'title' => $row->title,
                'description' => $row->description ?? '',
                'actor' => $row->actor_name,
                'module' => $row->module,
                'importance' => $row->importance,
                'action' => $row->action,
            ];
        })->all();
    }

    /**
     * @param  Builder<EmployeeActivityLog>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }
        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }
        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }
        if (! empty($filters['importance'])) {
            $query->where('importance', $filters['importance']);
        }
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';
            $query->where(function (Builder $builder) use ($needle): void {
                $builder->whereRaw('LOWER(title) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(actor_name) LIKE ?', [$needle]);
            });
        }
    }

    /**
     * @return array{module:string,action:string,title:string,description:string,importance:string,entity_type?:string,entity_id?:int}|null
     */
    private function mapRequest(string $method, string $path, Request $request): ?array
    {
        $relative = preg_replace('#^/?api/tenant/#', '', $path) ?? $path;
        $segments = array_values(array_filter(explode('/', $relative)));
        $root = $segments[0] ?? 'system';

        $catalog = [
            'invoices' => ['module' => 'sales', 'label' => 'الفواتير'],
            'orders' => ['module' => 'rental', 'label' => 'الطلبات'],
            'sales' => ['module' => 'sales', 'label' => 'المبيعات'],
            'payments' => ['module' => 'treasury', 'label' => 'المدفوعات'],
            'cashboxes' => ['module' => 'treasury', 'label' => 'الخزن'],
            'cash-movements' => ['module' => 'treasury', 'label' => 'حركات الصندوق'],
            'expenses' => ['module' => 'treasury', 'label' => 'المصروفات'],
            'customers' => ['module' => 'customers', 'label' => 'العملاء'],
            'suppliers' => ['module' => 'suppliers', 'label' => 'الموردون'],
            'purchase-orders' => ['module' => 'suppliers', 'label' => 'أوامر الشراء'],
            'dresses' => ['module' => 'inventory', 'label' => 'الفساتين'],
            'inventory' => ['module' => 'inventory', 'label' => 'المخزون'],
            'deliveries' => ['module' => 'delivery', 'label' => 'التسليمات'],
            'returns' => ['module' => 'delivery', 'label' => 'المرتجعات'],
            'hr' => ['module' => 'employees', 'label' => 'الموارد البشرية'],
            'employees' => ['module' => 'employees', 'label' => 'الموظفون'],
            'branches' => ['module' => 'system', 'label' => 'الفروع'],
            'accounting' => ['module' => 'system', 'label' => 'المحاسبة'],
            'tailoring' => ['module' => 'tailoring', 'label' => 'التفصيل'],
            'settings' => ['module' => 'system', 'label' => 'الإعدادات'],
        ];

        $info = $catalog[$root] ?? ['module' => 'system', 'label' => 'النظام'];
        $actionVerb = match ($method) {
            'POST' => 'إنشاء',
            'PUT', 'PATCH' => 'تحديث',
            'DELETE' => 'حذف',
            default => 'تنفيذ',
        };

        $importance = 'normal';
        $title = "{$actionVerb} — {$info['label']}";
        $description = "{$method} {$relative}";

        // Specialize important business actions
        if (str_contains($relative, 'returns') && str_contains($relative, 'settle')) {
            $title = 'تسوية مرتجع إيجار';
            $description = 'تم تنفيذ تسوية إرجاع لفاتورة إيجار';
            $importance = 'important';
        } elseif (str_contains($relative, 'dresses') && str_contains($relative, 'transfer')) {
            $title = 'نقل فستان بين الفروع';
            $description = 'تم نقل فستان/منتج بين فرعين';
            $importance = 'important';
        } elseif ($root === 'expenses' && $method === 'POST') {
            $title = 'تسجيل مصروف جديد';
            $description = 'تمت إضافة مصروف تشغيلي';
            $importance = 'important';
        } elseif ($root === 'cash-movements' || ($root === 'cashboxes' && $method !== 'GET')) {
            $title = 'حركة خزنة';
            $description = 'تم تنفيذ حركة مالية على الصندوق';
            $importance = 'important';
        } elseif ($root === 'hr' && str_contains($relative, 'leaves') && str_contains($relative, 'status')) {
            $status = (string) $request->input('status', '');
            $title = $status === 'approved' ? 'اعتماد طلب إجازة' : ($status === 'rejected' ? 'رفض طلب إجازة' : 'تحديث حالة إجازة');
            $description = 'تم تحديث حالة طلب إجازة موظف';
            $importance = 'important';
        } elseif ($root === 'hr' && str_contains($relative, 'employees') && str_contains($relative, 'status')) {
            $title = 'تغيير حالة موظف';
            $description = 'تم تغيير حالة موظف في الموارد البشرية';
            $importance = 'critical';
        } elseif ($root === 'hr' && str_contains($relative, 'payroll') && str_contains($relative, 'pay')) {
            $title = 'صرف رواتب';
            $description = 'تم تنفيذ عملية صرف رواتب';
            $importance = 'critical';
        } elseif ($root === 'invoices' || $root === 'orders' || $root === 'sales') {
            if ($method === 'POST') {
                $title = 'إنشاء فاتورة / طلب';
                $importance = 'important';
            }
        } elseif ($root === 'payments' && $method === 'POST') {
            $title = 'تسجيل دفعة';
            $description = 'تم تسجيل دفعة مالية';
            $importance = 'important';
        }

        $entityId = null;
        foreach ($segments as $seg) {
            if (ctype_digit($seg)) {
                $entityId = (int) $seg;
                break;
            }
        }

        return [
            'module' => $info['module'],
            'action' => strtolower($method).'_'.$root,
            'title' => $title,
            'description' => $description,
            'importance' => $importance,
            'entity_type' => $root,
            'entity_id' => $entityId,
        ];
    }

    private function notifyImportant(EmployeeActivityLog $row): void
    {
        try {
            $priority = $row->importance === 'critical' ? 'urgent' : 'high';
            $category = match ($row->module) {
                'sales', 'rental', 'tailoring' => $row->module === 'rental' ? 'rental' : ($row->module === 'tailoring' ? 'tailoring' : 'sales'),
                'treasury' => 'treasury',
                'delivery' => 'delivery',
                'employees' => 'employees',
                'inventory' => 'inventory',
                'customers' => 'customers',
                'suppliers' => 'suppliers',
                default => 'system',
            };

            $actor = $row->actor_name ?: 'موظف';
            // Broadcast so all authenticated users (including admins) see important ops alerts.
            $this->notifier->broadcast(
                $row->title,
                "{$actor}: ".($row->description ?: $row->title),
                $category,
                $priority,
                $this->actionUrlFor($row),
            );
        } catch (Throwable $e) {
            Log::warning('Failed to notify important activity', ['error' => $e->getMessage()]);
        }
    }

    private function actionUrlFor(EmployeeActivityLog $row): ?string
    {
        return match ($row->module) {
            'employees' => '/hr',
            'treasury' => '/cashboxes',
            'inventory' => '/dresses',
            'delivery' => '/returns',
            'sales', 'rental' => '/invoices',
            default => '/notifications',
        };
    }
}
