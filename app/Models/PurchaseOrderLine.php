<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderLine extends Model
{
    use HasUuid;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'line_no',
        'qty_ordered',
        'qty_received',
        'unit_price',
        'line_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'qty_ordered' => 'decimal:3',
            'qty_received' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function receiptLines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function qtyOutstanding(): float
    {
        return max(0, (float) $this->qty_ordered - (float) $this->qty_received);
    }
}
