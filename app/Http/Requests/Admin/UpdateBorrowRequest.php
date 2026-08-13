<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('borrows.update') ?? false;
    }

    public function rules(): array
    {
        return (new StoreBorrowRequest)->rules();
    }

    public function messages(): array
    {
        return (new StoreBorrowRequest)->messages();
    }
}
