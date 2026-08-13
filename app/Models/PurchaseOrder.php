<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasUuid;
    use SoftDeletes;

    public const DOCUMENT_TYPE = 'purchase_order';

    public const STATUSES = [
        'draft',
        'submitted',
        'approved',
        'rejected',
        'ordered',
        'partially_received',
        'received',
        'cancelled',
    ];

    protected $fillable = [
        'number',
        'vendor_id',
        'status',
        'order_date',
        'expected_date',
        'notes',
        'total_amount',
        'created_by',
        'submitted_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'total_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('line_no');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class)->latest();
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    public function canSubmit(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true) && $this->lines()->exists();
    }

    public function canReceive(): bool
    {
        return in_array($this->status, ['ordered', 'partially_received'], true);
    }

    public function refreshReceiveStatus(): void
    {
        $this->load('lines');

        $ordered = (float) $this->lines->sum('qty_ordered');
        $received = (float) $this->lines->sum('qty_received');

        if ($received <= 0) {
            return;
        }

        if ($received + 0.0001 >= $ordered) {
            $this->update(['status' => 'received']);
        } else {
            $this->update(['status' => 'partially_received']);
        }
    }
}
