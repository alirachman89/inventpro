<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use App\Models\Rack;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('racks.update') ?? false;
    }

    public function rules(): array
    {
        /** @var Rack $rack */
        $rack = $this->route('rack');

        $codeRules = [
            'required',
            'string',
            'max:32',
            'alpha_dash',
            Rule::unique('racks', 'code')
                ->ignore($rack->id)
                ->where(fn ($q) => $q->where('location_id', $rack->location_id)->whereNull('deleted_at')),
        ];

        if ($rack->isGeneral()) {
            $codeRules[] = Rule::in([Location::GENERAL_RACK_CODE]);
        } else {
            $codeRules[] = Rule::notIn([Location::GENERAL_RACK_CODE]);
        }

        return [
            'code' => $codeRules,
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var Rack $rack */
        $rack = $this->route('rack');

        if ($rack->isGeneral()) {
            $this->merge(['code' => Location::GENERAL_RACK_CODE]);
        } elseif ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
