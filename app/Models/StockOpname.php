<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasUuid;

    public const DOCUMENT_TYPE = 'stock_opname';

    public const STATUSES = [
        'draft',
        'submitted',
        'approved',
        'rejected',
        'posted',
        'cancelled',
    ];

    protected $fillable = [
        'number',
        'location_id',
        'status',
        'opname_date',
        'pic_user_id',
        'notes',
        'created_by',
        'submitted_at',
        'approved_at',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockOpnameLine::class);
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    public function canSubmit(): bool
    {
        if (! in_array($this->status, ['draft', 'rejected'], true)) {
            return false;
        }

        if (! $this->lines()->exists()) {
            return false;
        }

        return ! $this->lines()->whereNull('qty_counted')->exists();
    }

    public function canPost(): bool
    {
        return $this->status === 'approved';
    }

    public function varianceCount(): int
    {
        return $this->lines()
            ->whereNotNull('qty_variance')
            ->where('qty_variance', '!=', 0)
            ->count();
    }
}
