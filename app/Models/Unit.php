<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasUuid;
    use SoftDeletes;

    public const TYPES = ['count', 'weight', 'volume', 'length', 'other'];

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'type',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
