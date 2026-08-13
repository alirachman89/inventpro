<?php

namespace App\Http\Requests\Admin;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock_movements.create') ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type' => ['required', Rule::in(StockMovement::TYPES)],
            'reason' => ['required', 'string', Rule::in(StockMovement::reasonsFor((string) $type))],
            'movement_date' => ['required', 'date'],
            'client_id' => ['nullable', 'uuid', 'exists:clients,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'uuid', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.condition' => ['nullable', Rule::in(['good', 'damaged', 'quarantine', 'expired'])],
            'lines.*.from_location_id' => [
                Rule::requiredIf(in_array($type, ['out', 'transfer'], true)),
                'nullable',
                'uuid',
                'exists:locations,id',
            ],
            'lines.*.from_rack_id' => ['nullable', 'uuid', 'exists:racks,id'],
            'lines.*.to_location_id' => [
                Rule::requiredIf(in_array($type, ['in', 'transfer'], true)),
                'nullable',
                'uuid',
                'exists:locations,id',
            ],
            'lines.*.to_rack_id' => ['nullable', 'uuid', 'exists:racks,id'],
            'lines.*.asset_unit_id' => ['nullable', 'uuid', 'exists:asset_units,id'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))->map(function ($line) {
            foreach (['from_rack_id', 'to_rack_id', 'asset_unit_id'] as $key) {
                if (($line[$key] ?? '') === '') {
                    $line[$key] = null;
                }
            }

            return $line;
        })->all();

        if (($this->input('client_id') ?? '') === '') {
            $this->merge(['client_id' => null]);
        }

        $this->merge(['lines' => $lines]);
    }
}
