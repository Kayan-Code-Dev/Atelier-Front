<?php

namespace App\Http\Resources\Tenant;

use App\Services\Tenant\DressImageStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = $this->resolvePrimaryImageUrl();

        return [
            'id' => $this->id,
            'dress_category_id' => $this->dress_category_id,
            'dress_subcategory_id' => $this->dress_subcategory_id,
            'branch_id' => $this->branch_id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'size' => $this->size,
            'breast_size' => $this->breast_size,
            'waist_size' => $this->waist_size,
            'sleeve_size' => $this->sleeve_size,
            'measurements' => $this->measurements,
            'color' => $this->color,
            'purchase_price' => $this->purchase_price,
            'rental_price' => $this->rental_price,
            'sale_price' => $this->sale_price,
            'delivery_date' => $this->delivery_date?->toDateString(),
            'days_of_rent' => $this->days_of_rent,
            'occasion_datetime' => $this->occasion_datetime?->toISOString(),
            'visit_datetime' => $this->visit_datetime?->toISOString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'image' => $imageUrl,
            'images' => $this->whenLoaded('images', function () {
                $storage = app(DressImageStorageService::class);

                return $this->images
                    ->sortBy('sort_order')
                    ->values()
                    ->map(static fn ($image): array => [
                        'id' => $image->id,
                        'url' => $storage->url($image->path),
                        'is_primary' => (bool) $image->is_primary,
                        'sort_order' => (int) $image->sort_order,
                    ])
                    ->all();
            }),
            'display_name' => method_exists($this->resource, 'displayName')
                ? $this->resource->displayName()
                : $this->code,
            'category' => $this->whenLoaded('category', fn () => new DressCategoryResource($this->category)),
            'subcategory' => $this->whenLoaded('subcategory', fn () => new DressCategoryResource($this->subcategory)),
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }

    private function resolvePrimaryImageUrl(): ?string
    {
        if (! $this->relationLoaded('images')) {
            return null;
        }

        $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->sortBy('sort_order')->first();
        if ($primary === null) {
            return null;
        }

        return app(DressImageStorageService::class)->url($primary->path);
    }
}
