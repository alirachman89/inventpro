<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMovement extends Model
{
    use HasUuid;

    public const TYPES = ['in', 'out', 'transfer'];

    public const REASONS_IN = ['return', 'adjustment_in', 'other'];

    public const REASONS_OUT = ['issue', 'issue_to_client', 'damage', 'other'];

    public const REASONS_TRANSFER = ['transfer', 'other'];

    protected $fillable = [
        'number',
        'type',
        'reason',
        'movement_date',
        'client_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockMovementLine::class);
    }

    public static function reasonsFor(string $type): array
    {
        return match ($type) {
            'in' => self::REASONS_IN,
            'out' => self::REASONS_OUT,
            'transfer' => self::REASONS_TRANSFER,
            default => [],
        };
    }
}
