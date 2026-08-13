<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Rack;
use Illuminate\Support\Facades\DB;

class LocationService
{
    /**
     * @param  array{code: string, name: string, type: string, address?: ?string, is_active?: bool}  $data
     */
    public function createWithGeneralRack(array $data): Location
    {
        return DB::transaction(function () use ($data) {
            $location = Location::query()->create([
                'code' => strtoupper(trim($data['code'])),
                'name' => $data['name'],
                'type' => $data['type'],
                'address' => $data['address'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->ensureGeneralRack($location);

            return $location->fresh('racks');
        });
    }

    public function ensureGeneralRack(Location $location): Rack
    {
        return Rack::query()->firstOrCreate(
            [
                'location_id' => $location->id,
                'code' => Location::GENERAL_RACK_CODE,
            ],
            [
                'name' => 'General',
                'label' => 'Umum',
                'description' => 'Rak default lokasi',
                'is_default' => true,
                'is_active' => true,
            ],
        );
    }
}
