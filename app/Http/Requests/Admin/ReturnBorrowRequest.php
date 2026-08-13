<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReturnBorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('borrows.return') ?? false;
    }

    public function rules(): array
    {
        return [
            'returns' => ['required', 'array', 'min:1'],
            'returns.*.id' => ['required', 'uuid', 'exists:borrow_request_lines,id'],
            'returns.*.qty_return' => ['nullable', 'numeric', 'gt:0'],
            'returns.*.condition_on_return' => ['nullable', 'in:good,damaged,quarantine'],
        ];
    }
}
