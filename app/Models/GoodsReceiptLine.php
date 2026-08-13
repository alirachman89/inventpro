<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptLine extends Model
{
    use HasUuid;

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_line_id',
        'item_id',
        'rack_id',
        'qty_received',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'qty_received' => 'decimal:3',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }
}
