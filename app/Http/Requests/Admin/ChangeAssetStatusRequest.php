<?php

namespace App\Http\Requests\Admin;

use App\Models\AssetUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeAssetStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('asset_units.set_status') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(AssetUnit::MANUAL_STATUSES)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
