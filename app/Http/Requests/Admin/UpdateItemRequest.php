<?php

namespace App\Http\Requests\Admin;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('items.update') ?? false;
    }

    public function rules(): array
    {
        /** @var Item $item */
        $item = $this->route('item');

        return [
            'sku' => [
                'required',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique('items', 'sku')->ignore($item->id)->whereNull('deleted_at'),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('items', 'barcode')->ignore($item->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', Rule::in(Item::TYPES)],
            'is_serialized' => ['sometimes', 'boolean'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'uom_id' => ['required', 'uuid', 'exists:units,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sku')) {
            $this->merge(['sku' => strtoupper(trim((string) $this->input('sku')))]);
        }

        $itemType = $this->input('item_type', 'consumable');
        $this->merge([
            'is_serialized' => $itemType === 'asset' ? $this->boolean('is_serialized', true) : false,
            'is_active' => $this->boolean('is_active'),
            'min_stock' => $this->input('min_stock', 0),
        ]);
    }
}
