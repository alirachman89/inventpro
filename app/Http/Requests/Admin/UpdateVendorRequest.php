<?php

namespace App\Http\Requests\Admin;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vendors.update') ?? false;
    }

    public function rules(): array
    {
        /** @var Vendor $vendor */
        $vendor = $this->route('vendor');

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('vendors', 'code')->ignore($vendor->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }

        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
