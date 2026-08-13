<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasUuid;
    use SoftDeletes;

    public const TYPES = ['warehouse', 'site', 'transit', 'other'];

    public const GENERAL_RACK_CODE = 'GENERAL';

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function racks(): HasMany
    {
        return $this->hasMany(Rack::class)->orderByDesc('is_default')->orderBy('code');
    }

    public function defaultRack(): ?Rack
    {
        return $this->racks()->where('is_default', true)->first();
    }
}
