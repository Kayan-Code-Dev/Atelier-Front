<?php

namespace App\Http\Requests\Tenant\Dress;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DressRentalReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', Rule::in([
                'pending',
                'active',
                'overdue',
                'returned',
                'cancelled',
                'all',
                'valid',
            ])],
            'branch_id' => ['nullable', 'integer', Rule::exists('tenant.branches', 'id')->whereNull('deleted_at')],
            'customer_id' => ['nullable', 'integer', Rule::exists('tenant.customers', 'id')->whereNull('deleted_at')],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in([
                'created_at',
                'rent_start_date',
                'rent_end_date',
                'invoice_number',
                'total',
                'status',
            ])],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'journey_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'include_cancelled' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'status' => $validated['status'] ?? 'all',
            'branch_id' => isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            'customer_id' => isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            'search' => isset($validated['search']) ? trim((string) $validated['search']) : null,
            'sort' => $validated['sort'] ?? 'rent_start_date',
            'direction' => $validated['direction'] ?? 'desc',
            'page' => max(1, (int) ($validated['page'] ?? 1)),
            'per_page' => max(1, min(100, (int) ($validated['per_page'] ?? 15))),
            'journey_order' => $validated['journey_order'] ?? 'desc',
            'include_cancelled' => (bool) ($validated['include_cancelled'] ?? true),
        ];
    }
}
