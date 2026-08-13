<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rack extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'location_id',
        'code',
        'name',
        'label',
        'description',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function isGeneral(): bool
    {
        return strtoupper($this->code) === Location::GENERAL_RACK_CODE;
    }
}
