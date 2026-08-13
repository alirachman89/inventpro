<?php

namespace Database\Seeders;

use App\Models\AssetUnit;
use App\Models\Category;
use App\Models\Client;
use App\Models\Item;
use App\Models\Location;
use App\Models\Rack;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $stockService = app(StockService::class);

        $pcs = Unit::query()->where('code', 'pcs')->firstOrFail();
        $unit = Unit::query()->where('code', 'unit')->firstOrFail();
        $fastener = Category::query()->where('code', 'FASTENER')->first();
        $scaffold = Category::query()->where('code', 'SCAFFOLD')->first();

        $gudangUtama = Location::query()->where('code', 'GU-01')->firstOrFail();
        $siteB = Location::query()->where('code', 'SITE-B')->firstOrFail();

        $rack = fn (Location $location, string $code) => Rack::query()
            ->where('location_id', $location->id)
            ->where('code', $code)
            ->firstOrFail();

        $klem = Item::query()->updateOrCreate(
            ['sku' => 'KLEM-001'],
            [
                'name' => 'Klem',
                'barcode' => '8991001001001',
                'item_type' => 'consumable',
                'is_serialized' => false,
                'category_id' => $fastener?->id,
                'uom_id' => $pcs->id,
                'description' => 'Klem scaffolding — contoh penelusuran posisi multi lokasi/rak',
                'min_stock' => 50,
                'is_active' => true,
            ],
        );

        // Reset & seed positions for deterministic QC (idempotent-ish)
        $klem->stocks()->delete();
        $klem->ledgers()->delete();

        $positions = [
            [$gudangUtama, 'B-02', 120],
            [$gudangUtama, 'A-01', 30],
            [$gudangUtama, 'GENERAL', 5],
            [$siteB, 'RACK-01', 15],
        ];

        foreach ($positions as [$location, $code, $qty]) {
            $stockService->adjust(
                item: $klem,
                locationId: $location->id,
                rackId: $rack($location, $code)->id,
                qtyDelta: $qty,
                movementType: 'seed',
                notes: 'Seeder awal Klem',
            );
        }

        $frame = Item::query()->updateOrCreate(
            ['sku' => 'SCF-FRAME-01'],
            [
                'name' => 'Frame Scaffold',
                'barcode' => '8991002002002',
                'item_type' => 'asset',
                'is_serialized' => true,
                'category_id' => $scaffold?->id,
                'uom_id' => $unit->id,
                'description' => 'Contoh asset serialized',
                'min_stock' => 0,
                'is_active' => true,
            ],
        );

        $clientA = Client::query()->where('code', 'CLI-A')->first();
        $karyawanA = User::query()->where('email', 'warehouse@inventpro.local')->first();

        $assets = [
            ['AST-FRAME-001', 'SN-FR-001', 'available', 'GENERAL', null, null],
            ['AST-FRAME-002', 'SN-FR-002', 'maintenance', 'A-01', null, null],
            // Skenario: dipakai di Client A, dipegang Karyawan A (warehouse user sebagai contoh)
            ['AST-FRAME-003', 'SN-FR-003', 'borrowed', 'RACK-01', $clientA?->id, $karyawanA?->id],
        ];

        foreach ($assets as [$tag, $serial, $status, $rackCode, $clientId, $holderId]) {
            $location = str_starts_with($rackCode, 'RACK') ? $siteB : $gudangUtama;
            $resolvedRack = $rack($location, $rackCode);

            $asset = AssetUnit::query()->updateOrCreate(
                ['asset_tag' => $tag],
                [
                    'item_id' => $frame->id,
                    'serial_number' => $serial,
                    'status' => $status,
                    'condition' => 'good',
                    'location_id' => $location->id,
                    'rack_id' => $resolvedRack->id,
                    'current_client_id' => $clientId,
                    'current_holder_user_id' => $holderId,
                    'notes' => $clientId
                        ? 'Contoh: dipakai di Client A, pemegang Karyawan A'
                        : 'Seeder asset',
                ],
            );

            if ($asset->statusHistories()->count() === 0) {
                $asset->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => $status,
                    'notes' => 'Seeder awal',
                    'created_at' => now(),
                ]);
            }
        }
    }
}
