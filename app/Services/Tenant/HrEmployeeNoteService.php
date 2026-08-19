<?php

namespace App\Services\Tenant;

use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrEmployeeNote;
use App\Models\Tenant\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HrEmployeeNoteService
{
    public function paginate(int $employeeId, int $perPage = 30): LengthAwarePaginator
    {
        return HrEmployeeNote::query()
            ->with('author')
            ->where('employee_id', $employeeId)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(HrEmployee $employee, array $data, ?User $actor): HrEmployeeNote
    {
        return HrEmployeeNote::query()->create([
            'employee_id' => $employee->id,
            'author_id' => $actor?->id,
            'author_name' => $actor?->name ?? $actor?->email ?? 'نظام',
            'type' => $data['type'] ?? 'hr',
            'content' => $data['content'],
        ])->load('author');
    }

    public function findForEmployee(int $employeeId, int $noteId): HrEmployeeNote
    {
        return HrEmployeeNote::query()
            ->where('employee_id', $employeeId)
            ->findOrFail($noteId);
    }

    public function delete(HrEmployeeNote $note): void
    {
        $note->delete();
    }
}
