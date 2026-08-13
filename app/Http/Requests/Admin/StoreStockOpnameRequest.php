<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock_opnames.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'uuid', 'exists:locations,id'],
            'opname_date' => ['required', 'date'],
            'pic_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (($this->input('pic_user_id') ?? '') === '') {
            $this->merge(['pic_user_id' => null]);
        }
    }
}
