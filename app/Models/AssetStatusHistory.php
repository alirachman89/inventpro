<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetStatusHistory extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'asset_unit_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function assetUnit(): BelongsTo
    {
        return $this->belongsTo(AssetUnit::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
