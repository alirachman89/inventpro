<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Location;
use App\Models\Rack;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    /**
     * Adjust on-hand qty at item+location+rack. Positive = in, negative = out.
     */
    public function adjust(
        Item $item,
        string $locationId,
        ?string $rackId,
        float $qtyDelta,
        string $movementType,
        ?User $actor = null,
        ?string $notes = null,
        string $condition = 'good',
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): ItemStock {
        if ($qtyDelta == 0.0) {
            throw new InvalidArgumentException('Qty penyesuaian tidak boleh 0.');
        }

        $rack = $this->resolveRack($locationId, $rackId);

        return DB::transaction(function () use ($item, $locationId, $rack, $qtyDelta, $movementType, $actor, $notes, $condition, $referenceType, $referenceId) {
            $stock = ItemStock::query()->firstOrCreate(
                [
                    'item_id' => $item->id,
                    'location_id' => $locationId,
                    'rack_id' => $rack->id,
                    'condition' => $condition,
                ],
                [
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                ],
            );

            $stock = ItemStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();

            $before = (float) $stock->qty_on_hand;
            $after = $before + $qtyDelta;

            if ($after < 0) {
                throw new InvalidArgumentException('Stok tidak mencukupi untuk penyesuaian ini.');
            }

            $stock->qty_on_hand = $after;
            $stock->save();

            StockLedger::query()->create([
                'item_id' => $item->id,
                'location_id' => $locationId,
                'rack_id' => $rack->id,
                'movement_type' => $movementType,
                'qty_delta' => $qtyDelta,
                'qty_before' => $before,
                'qty_after' => $after,
                'condition' => $condition,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $actor?->id,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            return $stock->fresh(['location', 'rack']);
        });
    }

    public function resolveRack(string $locationId, ?string $rackId): Rack
    {
        if ($rackId) {
            $rack = Rack::query()
                ->where('id', $rackId)
                ->where('location_id', $locationId)
                ->where('is_active', true)
                ->first();

            if (! $rack) {
                throw new InvalidArgumentException('Rak tidak valid untuk lokasi ini.');
            }

            return $rack;
        }

        $location = Location::query()->findOrFail($locationId);
        $general = $location->racks()
            ->where('code', Location::GENERAL_RACK_CODE)
            ->where('is_active', true)
            ->first();

        if (! $general) {
            $general = app(LocationService::class)->ensureGeneralRack($location);
        }

        return $general;
    }
}
