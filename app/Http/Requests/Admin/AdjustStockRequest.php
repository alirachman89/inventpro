<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('item_stocks.adjust') ?? false;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'uuid', 'exists:locations,id'],
            'rack_id' => ['nullable', 'uuid', 'exists:racks,id'],
            'qty' => ['required', 'numeric', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'condition' => ['required', Rule::in(['good', 'damaged', 'quarantine', 'expired'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('rack_id') === '' || $this->input('rack_id') === null) {
            $this->merge(['rack_id' => null]);
        }
    }
}
