<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasUuid;
    use SoftDeletes;

    public const TYPES = [
        'company',
        'project_site',
        'individual',
        'internal_unit',
        'other',
    ];

    protected $fillable = [
        'code',
        'name',
        'type',
        'contact_person',
        'email',
        'phone',
        'address',
        'tax_id',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function assetUnits(): HasMany
    {
        return $this->hasMany(AssetUnit::class, 'current_client_id');
    }

    public function borrowRequests(): HasMany
    {
        return $this->hasMany(BorrowRequest::class);
    }
}
