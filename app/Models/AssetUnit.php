<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetUnit extends Model
{
    use HasUuid;
    use SoftDeletes;

    public const STATUSES = [
        'available',
        'reserved',
        'borrowed',
        'in_transit',
        'maintenance',
        'damaged',
        'quarantine',
        'lost',
        'disposed',
    ];

    public const MANUAL_STATUSES = [
        'available',
        'maintenance',
        'damaged',
        'quarantine',
        'lost',
        'disposed',
    ];

    public const CONDITIONS = ['good', 'damaged', 'quarantine', 'expired'];

    protected $fillable = [
        'item_id',
        'asset_tag',
        'serial_number',
        'status',
        'condition',
        'location_id',
        'rack_id',
        'current_holder_user_id',
        'current_client_id',
        'notes',
    ];

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

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_holder_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'current_client_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(AssetStatusHistory::class)->orderByDesc('created_at');
    }
}
