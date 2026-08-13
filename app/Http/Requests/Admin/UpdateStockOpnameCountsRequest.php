<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockOpnameCountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock_opnames.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'uuid', 'exists:stock_opname_lines,id'],
            'lines.*.qty_counted' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
