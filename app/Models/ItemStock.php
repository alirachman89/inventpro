<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemStock extends Model
{
    use HasUuid;

    public const CONDITIONS = ['good', 'damaged', 'quarantine', 'expired'];

    protected $fillable = [
        'item_id',
        'location_id',
        'rack_id',
        'qty_on_hand',
        'qty_reserved',
        'condition',
    ];

    protected function casts(): array
    {
        return [
            'qty_on_hand' => 'decimal:3',
            'qty_reserved' => 'decimal:3',
        ];
    }

    public function getQtyAvailableAttribute(): float
    {
        return max(0, (float) $this->qty_on_hand - (float) $this->qty_reserved);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }
}
