<?php

namespace App\Http\Resources\Tenant;

use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantUserAvatarService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tenant = app(TenantContext::class)->tenant();

        $avatarUrl = app(TenantUserAvatarService::class)->urlForUser($this->resource, $tenant);

        $branchName = null;
        $branchIds = [];
        $branches = [];
        $employeeId = null;
        $employeeName = null;

        $employee = $this->relationLoaded('hrEmployee') ? $this->hrEmployee : $this->hrEmployee()->with(['branch', 'branches'])->first();
        if ($employee) {
            $employeeId = (int) $employee->id;
            $employeeName = (string) $employee->full_name;
            if ($employee->relationLoaded('branch')) {
                $branchName = $employee->branch?->name;
            }
            if ($employee->branch_id) {
                $branchIds[] = (int) $employee->branch_id;
            }
            if ($employee->relationLoaded('branches')) {
                foreach ($employee->branches as $branch) {
                    $branchIds[] = (int) $branch->id;
                    $branches[] = ['id' => (int) $branch->id, 'name' => (string) $branch->name];
                }
            }
        }
        if ($this->branch_id) {
            $branchIds[] = (int) $this->branch_id;
        }
        $branchIds = array_values(array_unique($branchIds));

        $isOwner = method_exists($this->resource, 'isOwner') ? (bool) $this->resource->isOwner() : false;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'branch_id' => $this->branch_id ?: ($branchIds[0] ?? null),
            'branch_ids' => $branchIds,
            'branches' => $branches,
            'branch_name' => $branchName,
            'employee_id' => $employeeId,
            'employee_name' => $employeeName,
            'lock_invoice_employee' => $employeeId !== null && ! $isOwner,
            'can_select_invoice_branch' => $isOwner || count($branchIds) > 1,
            'status' => $this->status,
            'avatar_path' => $this->avatar_path,
            'avatar_url' => $avatarUrl,
            'avatar' => $avatarUrl,
        ];
    }
}
