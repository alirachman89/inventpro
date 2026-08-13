<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('locations.update') ?? false;
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
                Rule::unique('locations', 'code')->ignore($location->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Location::TYPES)],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
