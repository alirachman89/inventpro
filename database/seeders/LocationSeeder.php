<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Rack;
use App\Services\LocationService;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(LocationService::class);

        $gudangUtama = Location::query()->where('code', 'GU-01')->first();
        if (! $gudangUtama) {
            $gudangUtama = $service->createWithGeneralRack([
                'code' => 'GU-01',
                'name' => 'Gudang Utama',
                'type' => 'warehouse',
                'address' => 'Jl. Gudang Utama No. 1',
                'is_active' => true,
            ]);
        } else {
            $service->ensureGeneralRack($gudangUtama);
        }

        $this->upsertRack($gudangUtama, 'A-01', 'Rak A-01', 'Rak Besi Zona A');
        $this->upsertRack($gudangUtama, 'B-02', 'Rak B-02', 'Rak Besi Zona B');

        $siteB = Location::query()->where('code', 'SITE-B')->first();
        if (! $siteB) {
            $siteB = $service->createWithGeneralRack([
                'code' => 'SITE-B',
                'name' => 'Gudang Site B',
                'type' => 'site',
                'address' => 'Area Proyek Site B',
                'is_active' => true,
            ]);
        } else {
            $service->ensureGeneralRack($siteB);
        }

        $this->upsertRack($siteB, 'RACK-01', 'Rak Site 01', 'Rak Outdoor Site B');
        $this->upsertRack($siteB, 'RACK-02', 'Rak Site 02', 'Rak Indoor Site B');
    }

    private function upsertRack(Location $location, string $code, string $name, string $label): void
    {
        Rack::query()->updateOrCreate(
            [
                'location_id' => $location->id,
                'code' => $code,
            ],
            [
                'name' => $name,
                'label' => $label,
                'is_default' => false,
                'is_active' => true,
            ],
        );
    }
}
