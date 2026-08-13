<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovementLine extends Model
{
    use HasUuid;

    protected $fillable = [
        'stock_movement_id',
        'item_id',
        'qty',
        'condition',
        'from_location_id',
        'from_rack_id',
        'to_location_id',
        'to_rack_id',
        'asset_unit_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function fromRack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'from_rack_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function toRack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'to_rack_id');
    }

    public function assetUnit(): BelongsTo
    {
        return $this->belongsTo(AssetUnit::class);
    }
}
