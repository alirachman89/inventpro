<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowRequestLine extends Model
{
    use HasUuid;

    protected $fillable = [
        'borrow_request_id',
        'item_id',
        'asset_unit_id',
        'qty',
        'qty_checked_out',
        'qty_returned',
        'from_location_id',
        'from_rack_id',
        'condition_on_return',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'qty_checked_out' => 'decimal:3',
            'qty_returned' => 'decimal:3',
        ];
    }

    public function borrowRequest(): BelongsTo
    {
        return $this->belongsTo(BorrowRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function assetUnit(): BelongsTo
    {
        return $this->belongsTo(AssetUnit::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function fromRack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'from_rack_id');
    }

    public function outstandingQty(): float
    {
        return max(0, (float) $this->qty_checked_out - (float) $this->qty_returned);
    }
}
