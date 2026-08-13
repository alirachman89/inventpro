<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'location_id',
        'rack_id',
        'movement_type',
        'qty_delta',
        'qty_before',
        'qty_after',
        'condition',
        'reference_type',
        'reference_id',
        'created_by',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'qty_delta' => 'decimal:3',
            'qty_before' => 'decimal:3',
            'qty_after' => 'decimal:3',
            'created_at' => 'datetime',
        ];
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
