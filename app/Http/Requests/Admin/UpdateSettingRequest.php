<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_phone' => ['nullable', 'string', 'max:64'],
            'timezone' => ['required', 'timezone'],
            'prefix_po' => ['required', 'string', 'max:16', 'alpha_dash'],
            'prefix_gr' => ['required', 'string', 'max:16', 'alpha_dash'],
            'prefix_opname' => ['required', 'string', 'max:16', 'alpha_dash'],
            'prefix_borrow' => ['required', 'string', 'max:16', 'alpha_dash'],
            'prefix_movement' => ['required', 'string', 'max:16', 'alpha_dash'],
        ];
    }
}
