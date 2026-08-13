<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Item;
use App\Models\Location;
use App\Models\Rack;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockMovementService;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        if (StockMovement::query()->exists()) {
            return;
        }

        $service = app(StockMovementService::class);
        $actor = User::query()->where('email', 'warehouse@inventpro.local')->first()
            ?? User::query()->firstOrFail();

        $klem = Item::query()->where('sku', 'KLEM-001')->firstOrFail();
        $clientA = Client::query()->where('code', 'CLI-A')->firstOrFail();
        $gudangUtama = Location::query()->where('code', 'GU-01')->firstOrFail();
        $siteB = Location::query()->where('code', 'SITE-B')->firstOrFail();

        $rack = fn (Location $location, string $code) => Rack::query()
            ->where('location_id', $location->id)
            ->where('code', $code)
            ->firstOrFail();

        $guGeneral = $rack($gudangUtama, 'GENERAL');
        $guA01 = $rack($gudangUtama, 'A-01');
        $siteRack = $rack($siteB, 'RACK-01');

        // Stock In → GU-01 / GENERAL
        $service->create([
            'type' => 'in',
            'reason' => 'return',
            'movement_date' => now()->toDateString(),
            'notes' => 'Seeder: Stock In sample',
            'lines' => [[
                'item_id' => $klem->id,
                'qty' => 10,
                'condition' => 'good',
                'to_location_id' => $gudangUtama->id,
                'to_rack_id' => $guGeneral->id,
            ]],
        ], $actor);

        // Stock Out (issue_to_client) dari GU-01 / A-01
        $service->create([
            'type' => 'out',
            'reason' => 'issue_to_client',
            'movement_date' => now()->toDateString(),
            'client_id' => $clientA->id,
            'notes' => 'Seeder: Issue ke Client A',
            'lines' => [[
                'item_id' => $klem->id,
                'qty' => 5,
                'condition' => 'good',
                'from_location_id' => $gudangUtama->id,
                'from_rack_id' => $guA01->id,
            ]],
        ], $actor);

        // Transfer GU-01/A-01 → SITE-B/RACK-01
        $service->create([
            'type' => 'transfer',
            'reason' => 'transfer',
            'movement_date' => now()->toDateString(),
            'notes' => 'Seeder: Transfer multi posisi',
            'lines' => [[
                'item_id' => $klem->id,
                'qty' => 3,
                'condition' => 'good',
                'from_location_id' => $gudangUtama->id,
                'from_rack_id' => $guA01->id,
                'to_location_id' => $siteB->id,
                'to_rack_id' => $siteRack->id,
            ]],
        ], $actor);
    }
}
