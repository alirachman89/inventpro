<?php

namespace App\Http\Requests\Admin;

use App\Models\AssetUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('asset_units.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'asset_tag' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('asset_units', 'asset_tag')->whereNull('deleted_at')],
            'serial_number' => ['nullable', 'string', 'max:128'],
            'status' => ['required', Rule::in(AssetUnit::MANUAL_STATUSES)],
            'condition' => ['required', Rule::in(AssetUnit::CONDITIONS)],
            'location_id' => ['required', 'uuid', 'exists:locations,id'],
            'rack_id' => ['nullable', 'uuid', 'exists:racks,id'],
            'current_client_id' => ['nullable', 'uuid', 'exists:clients,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('asset_tag')) {
            $this->merge(['asset_tag' => strtoupper(trim((string) $this->input('asset_tag')))]);
        }

        if ($this->input('rack_id') === '' || $this->input('rack_id') === null) {
            $this->merge(['rack_id' => null]);
        }

        if ($this->input('current_client_id') === '' || $this->input('current_client_id') === null) {
            $this->merge(['current_client_id' => null]);
        }
    }
}
