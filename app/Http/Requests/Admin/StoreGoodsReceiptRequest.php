<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('goods_receipts.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'uuid', 'exists:locations,id'],
            'received_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'uuid', 'exists:purchase_order_lines,id'],
            'lines.*.rack_id' => ['nullable', 'uuid', 'exists:racks,id'],
            'lines.*.qty_received' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))->map(function ($line) {
            if (($line['rack_id'] ?? '') === '') {
                $line['rack_id'] = null;
            }

            return $line;
        })->all();

        $this->merge(['lines' => $lines]);
    }
}
