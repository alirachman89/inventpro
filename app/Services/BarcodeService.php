<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Str;

class BarcodeService
{
    /**
     * Generate a unique CODE128-friendly barcode for an item.
     */
    public function generate(?string $sku = null, ?string $excludeItemId = null): string
    {
        $base = $this->normalizeSku($sku);

        if ($base === '') {
            $base = 'INV'.now()->format('ymdHis');
        } else {
            $base = 'INV'.$base;
        }

        $base = Str::limit($base, 48, '');

        $candidate = $base;
        $attempt = 0;

        while ($this->exists($candidate, $excludeItemId)) {
            $attempt++;
            $suffix = $attempt < 10
                ? str_pad((string) $attempt, 2, '0', STR_PAD_LEFT)
                : Str::upper(Str::random(4));
            $candidate = Str::limit($base, 48, '').$suffix;

            if ($attempt > 50) {
                $candidate = 'INV'.Str::upper(Str::random(12));
            }
        }

        return $candidate;
    }

    public function exists(string $barcode, ?string $excludeItemId = null): bool
    {
        return Item::query()
            ->where('barcode', $barcode)
            ->when($excludeItemId, fn ($q) => $q->where('id', '!=', $excludeItemId))
            ->exists();
    }

    private function normalizeSku(?string $sku): string
    {
        if ($sku === null || trim($sku) === '') {
            return '';
        }

        // CODE128-safe-ish: A-Z 0-9 hyphen
        $clean = preg_replace('/[^A-Za-z0-9\-]/', '', strtoupper(trim($sku))) ?? '';

        return $clean;
    }
}
