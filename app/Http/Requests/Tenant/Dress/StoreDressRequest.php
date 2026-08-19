<?php

namespace App\Http\Requests\Tenant\Dress;

use App\Models\Tenant\Dress;
use App\Models\Tenant\DressCategory;
use App\Support\WesternDigits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('code')) {
            $merge['code'] = WesternDigits::normalize($this->input('code'));
        }

        // FE / clients often send rent_price; service persists rental_price.
        if ($this->filled('rent_price') && ! $this->filled('rental_price')) {
            $merge['rental_price'] = $this->input('rent_price');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'dress_category_id' => ['required', 'integer', Rule::exists('tenant.dress_categories', 'id')->whereNull('deleted_at')],
            'dress_subcategory_id' => ['required', 'integer', Rule::exists('tenant.dress_categories', 'id')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:100', Rule::unique('tenant.dresses', 'code')->whereNull('deleted_at')],
            'branch_id' => ['required', 'integer', Rule::exists('tenant.branches', 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(Dress::statuses())],
            'size' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'breast_size' => ['nullable', 'string', 'max:50'],
            'waist_size' => ['nullable', 'string', 'max:50'],
            'sleeve_size' => ['nullable', 'string', 'max:50'],
            'measurements' => ['nullable', 'array'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'rental_price' => ['nullable', 'numeric', 'min:0'],
            'rent_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $categoryId = (int) $this->input('dress_category_id');
            $subcategoryId = (int) $this->input('dress_subcategory_id');

            if ($categoryId <= 0 || $subcategoryId <= 0) {
                return;
            }

            $category = DressCategory::query()->find($categoryId);
            if ($category !== null && $category->parent_id !== null) {
                $validator->errors()->add('dress_category_id', 'The selected category must be a parent category.');
            }

            $subcategory = DressCategory::query()->find($subcategoryId);
            if ($subcategory === null) {
                return;
            }

            if ($subcategory->parent_id === null) {
                $validator->errors()->add('dress_subcategory_id', 'The selected subcategory must belong to a parent category.');
            } elseif ((int) $subcategory->parent_id !== $categoryId) {
                $validator->errors()->add('dress_subcategory_id', 'The selected subcategory does not belong to the selected category.');
            }
        });
    }
}
