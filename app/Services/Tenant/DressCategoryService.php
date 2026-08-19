<?php

namespace App\Services\Tenant;

use App\Enums\CustomerStatus;
use App\Models\Tenant\DressCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class DressCategoryService
{
    public function paginate(
        ?string $search = null,
        ?string $status = null,
        mixed $parentId = null,
        bool $onlyParents = false,
        bool $onlyChildren = false,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = DressCategory::query()
            ->with(['parent', 'children'])
            ->latest('id');

        $searchTerm = trim((string) $search);
        if ($searchTerm !== '') {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($searchTerm).'%']);
        }

        $statusValue = trim((string) $status);
        if ($statusValue !== '') {
            $query->where('status', $statusValue);
        }

        $parentIdValue = trim((string) $parentId);
        if ($parentIdValue !== '') {
            $query->where('parent_id', (int) $parentIdValue);
        }

        if ($onlyParents) {
            $query->whereNull('parent_id');
        }

        if ($onlyChildren) {
            $query->whereNotNull('parent_id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): DressCategory
    {
        $category = DressCategory::query()->create($this->filterCategoryInput($data, isCreate: true));
        $category->load(['parent', 'children']);

        // Parent categories need at least one child — dress create and PO receive require subcategory_id,
        // while the categories UI currently has no parent_id field for creating children.
        if ($category->parent_id === null) {
            $this->ensureDefaultChild($category);
            $category->load('children');
        }

        return $category;
    }

    /**
     * Ensure a parent category has a usable default subcategory (name: عام).
     */
    public function ensureDefaultChild(DressCategory $parent, string $name = 'عام'): DressCategory
    {
        if ($parent->parent_id !== null) {
            return $parent;
        }

        $existing = DressCategory::query()
            ->where('parent_id', $parent->id)
            ->orderBy('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DressCategory::query()->create($this->filterCategoryInput([
            'name' => $name,
            'parent_id' => $parent->id,
            'status' => CustomerStatus::ACTIVE->value,
            'description' => 'قسم فرعي افتراضي',
        ], isCreate: true));
    }

    public function findOrFail(int $categoryId): DressCategory
    {
        return DressCategory::query()
            ->with(['parent', 'children'])
            ->findOrFail($categoryId);
    }

    public function update(DressCategory $category, array $data): DressCategory
    {
        $category->fill($this->filterCategoryInput($data));
        $category->save();

        return $category->refresh()->load(['parent', 'children']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterCategoryInput(array $data, bool $isCreate = false): array
    {
        $allowed = ['name', 'parent_id', 'slug', 'description', 'status'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        if ($isCreate && ! array_key_exists('status', $filtered)) {
            $filtered['status'] = CustomerStatus::ACTIVE->value;
        }

        if (array_key_exists('name', $filtered) && ! array_key_exists('slug', $filtered)) {
            $slug = Str::slug((string) $filtered['name']);
            // Arabic (and other non-Latin) names yield an empty slug — fall back to a unique key.
            if ($slug === '') {
                $slug = 'cat-'.Str::lower(Str::random(10));
            }
            $filtered['slug'] = $slug;
        }

        return $filtered;
    }

    public function delete(DressCategory $category): void
    {
        $category->delete();
    }
}
