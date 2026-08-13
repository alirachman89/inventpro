<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'uuid', 'exists:vendors,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'uuid', 'exists:items,id'],
            'lines.*.qty_ordered' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
