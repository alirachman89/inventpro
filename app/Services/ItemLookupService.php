<?php

namespace App\Services;

use App\Models\Item;

class ItemLookupService
{
    /**
     * Exact match by barcode or SKU (active items preferred).
     *
     * @return array<string, mixed>|null
     */
    public function lookup(string $code): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $item = Item::query()
            ->with(['uom:id,code,name', 'category:id,code,name'])
            ->where(function ($q) use ($code) {
                $q->where('barcode', $code)
                    ->orWhereRaw('LOWER(sku) = ?', [mb_strtolower($code)]);
            })
            ->orderByDesc('is_active')
            ->first();

        if (! $item) {
            return null;
        }

        return [
            'id' => $item->id,
            'sku' => $item->sku,
            'barcode' => $item->barcode,
            'name' => $item->name,
            'item_type' => $item->item_type,
            'is_serialized' => (bool) $item->is_serialized,
            'is_active' => (bool) $item->is_active,
            'uom' => $item->uom ? $item->uom->code : null,
            'category' => $item->category?->name,
        ];
    }
}
