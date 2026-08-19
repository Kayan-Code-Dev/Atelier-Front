<?php

namespace App\Support\Tenant;

use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AuthorizedBranchAccess
{
    /**
     * @return list<int>|null null = unrestricted (owner or unassigned)
     */
    public function ids(?User $user = null): ?array
    {
        $user ??= $this->currentUser();
        if (! $user instanceof User) {
            return null;
        }

        if ($user->isOwner()) {
            return null;
        }

        $ids = [];
        if ($user->branch_id) {
            $ids[] = (int) $user->branch_id;
        }

        $employee = $user->relationLoaded('hrEmployee')
            ? $user->hrEmployee
            : $user->hrEmployee()->with('branches')->first();

        if ($employee) {
            if ($employee->branch_id) {
                $ids[] = (int) $employee->branch_id;
            }
            if ($employee->relationLoaded('branches')) {
                foreach ($employee->branches as $branch) {
                    $ids[] = (int) $branch->id;
                }
            } else {
                foreach ($employee->branches()->pluck('branches.id') as $id) {
                    $ids[] = (int) $id;
                }
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return $ids === [] ? null : $ids;
    }

    public function constrain(Builder $query, string $column = 'branch_id'): void
    {
        $ids = $this->ids();
        if ($ids === null) {
            return;
        }

        $query->whereIn($column, $ids);
    }

    public function constrainEmployeeQuery(Builder $query): void
    {
        $ids = $this->ids();
        if ($ids === null) {
            return;
        }

        $query->where(function (Builder $builder) use ($ids): void {
            $builder->whereIn('branch_id', $ids)
                ->orWhereHas('branches', fn (Builder $inner) => $inner->whereIn('branches.id', $ids));
        });
    }

    public function constrainRelation(Builder $query, string $relation, string $column = 'branch_id'): void
    {
        $ids = $this->ids();
        if ($ids === null) {
            return;
        }

        $query->whereHas($relation, fn (Builder $inner) => $inner->whereIn($column, $ids));
    }

    public function assertCanUse(?int $branchId): void
    {
        $ids = $this->ids();
        if ($ids === null) {
            return;
        }

        if ($branchId === null || ! in_array($branchId, $ids, true)) {
            throw ValidationException::withMessages([
                'branch_id' => ['لا يمكنك العمل إلا على الفرع المعيَّن لك.'],
            ]);
        }
    }

    public function lockedEmployeeId(?User $user = null): ?int
    {
        $user ??= $this->currentUser();
        if (! $user instanceof User || $user->isOwner()) {
            return null;
        }

        $employee = $user->relationLoaded('hrEmployee')
            ? $user->hrEmployee
            : $user->hrEmployee()->first();

        return $employee?->id ? (int) $employee->id : null;
    }

    public function applyInvoiceActorDefaults(array $data, ?int $actorId = null): array
    {
        $user = $this->currentUser() ?? ($actorId ? \App\Models\Tenant\User::query()->find($actorId) : null);
        $user = $user instanceof \App\Models\Tenant\User ? $user : null;
        $lockedEmployeeId = $this->lockedEmployeeId($user);
        if ($lockedEmployeeId) {
            $data['employee_id'] = $lockedEmployeeId;
        }

        $ids = $this->ids($user);
        if ($ids === null) {
            return $data;
        }

        $requested = isset($data['branch_id']) ? (int) $data['branch_id'] : 0;
        if (count($ids) === 1) {
            $data['branch_id'] = $ids[0];
        } elseif ($requested > 0) {
            $this->assertCanUse($requested);
        } else {
            throw ValidationException::withMessages([
                'branch_id' => ['اختر الفرع الذي تريد إنشاء الفاتورة عليه.'],
            ]);
        }

        return $data;
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
