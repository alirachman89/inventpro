<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameLine extends Model
{
    use HasUuid;

    protected $fillable = [
        'stock_opname_id',
        'item_id',
        'rack_id',
        'condition',
        'qty_system',
        'qty_counted',
        'qty_variance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'qty_system' => 'decimal:3',
            'qty_counted' => 'decimal:3',
            'qty_variance' => 'decimal:3',
        ];
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function recalculateVariance(): void
    {
        if ($this->qty_counted === null) {
            $this->qty_variance = null;

            return;
        }

        $this->qty_variance = (float) $this->qty_counted - (float) $this->qty_system;
    }
}
