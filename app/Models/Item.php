<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasUuid;
    use SoftDeletes;

    public const TYPES = ['consumable', 'asset'];

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'item_type',
        'is_serialized',
        'category_id',
        'uom_id',
        'description',
        'min_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_serialized' => 'boolean',
            'is_active' => 'boolean',
            'min_stock' => 'decimal:3',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'uom_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ItemStock::class);
    }

    public function assetUnits(): HasMany
    {
        return $this->hasMany(AssetUnit::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    public function isAsset(): bool
    {
        return $this->item_type === 'asset';
    }

    public function totalAvailable(): float
    {
        return (float) $this->stocks->sum(fn (ItemStock $stock) => $stock->qty_available);
    }

    public function isLowStock(): bool
    {
        return $this->totalAvailable() < (float) $this->min_stock;
    }
}
