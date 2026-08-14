<?php

namespace App\Services;

use App\Models\AssetUnit;

class AssetLookupService
{
    /**
     * Exact match by asset_tag or serial_number.
     *
     * @return array<string, mixed>|null
     */
    public function lookup(string $code, bool $availableOnly = false): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $asset = AssetUnit::query()
            ->with([
                'item:id,sku,name,barcode,is_active',
                'location:id,code,name',
                'rack:id,code,label',
            ])
            ->where(function ($q) use ($code) {
                $q->where('asset_tag', $code)
                    ->orWhereRaw('LOWER(serial_number) = ?', [mb_strtolower($code)]);
            })
            ->when($availableOnly, fn ($q) => $q->where('status', 'available'))
            ->first();

        if (! $asset) {
            return null;
        }

        return [
            'id' => $asset->id,
            'asset_tag' => $asset->asset_tag,
            'serial_number' => $asset->serial_number,
            'status' => $asset->status,
            'condition' => $asset->condition,
            'item' => $asset->item ? [
                'id' => $asset->item->id,
                'sku' => $asset->item->sku,
                'name' => $asset->item->name,
                'barcode' => $asset->item->barcode,
            ] : null,
            'location_code' => $asset->location?->code,
            'rack_code' => $asset->rack?->code,
        ];
    }
}
