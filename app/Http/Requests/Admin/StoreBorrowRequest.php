<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('borrows.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'borrower_user_id' => ['required', 'uuid', 'exists:users,id'],
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'borrow_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:borrow_date'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.asset_unit_id' => ['nullable', 'uuid', 'exists:asset_units,id'],
            'lines.*.item_id' => ['nullable', 'uuid', 'exists:items,id'],
            'lines.*.qty' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.from_location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'lines.*.from_rack_id' => ['nullable', 'uuid', 'exists:racks,id'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'borrower_user_id.required' => 'Peminjam (Karyawan) wajib diisi.',
            'client_id.required' => 'Digunakan di Client wajib diisi.',
        ];
    }
}
