<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('racks.create') ?? false;
    }

    public function rules(): array
    {
        /** @var Location $location */
        $location = $this->route('location');

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('racks', 'code')
                    ->where(fn ($q) => $q->where('location_id', $location->id)->whereNull('deleted_at')),
                Rule::notIn([Location::GENERAL_RACK_CODE]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }

        if (! $this->filled('label') && $this->filled('code')) {
            $this->merge(['label' => strtoupper(trim((string) $this->input('code')))]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    public function messages(): array
    {
        return [
            'code.not_in' => 'Kode GENERAL dibuat otomatis dan tidak dapat ditambahkan manual.',
        ];
    }
}
