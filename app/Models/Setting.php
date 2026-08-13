<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasUuid;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $settings = static::allCached();

        return $settings[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_scalar($value) || $value === null
                        ? (string) ($value ?? '')
                        : json_encode($value),
                    'group' => $group,
                ],
            );
        }

        Cache::forget('app.settings');
    }

    /**
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        return Cache::rememberForever('app.settings', function () {
            return static::query()->pluck('value', 'key')->all();
        });
    }
}
