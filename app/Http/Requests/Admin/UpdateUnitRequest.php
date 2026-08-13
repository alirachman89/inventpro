<?php

namespace App\Http\Requests\Admin;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('units.update') ?? false;
    }

    public function rules(): array
    {
        /** @var Unit $unit */
        $unit = $this->route('unit');

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('units', 'code')->ignore($unit->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:32'],
            'type' => ['required', Rule::in(Unit::TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtolower(trim((string) $this->input('code')))]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
