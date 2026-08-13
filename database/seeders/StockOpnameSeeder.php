<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\StockOpnameService;
use Illuminate\Database\Seeder;

class StockOpnameSeeder extends Seeder
{
    public function run(): void
    {
        if (StockOpname::query()->exists()) {
            return;
        }

        $service = app(StockOpnameService::class);
        $actor = User::query()->where('email', 'warehouse@inventpro.local')->first()
            ?? User::query()->firstOrFail();
        $gudangUtama = Location::query()->where('code', 'GU-01')->firstOrFail();

        $opname = $service->create([
            'location_id' => $gudangUtama->id,
            'opname_date' => now()->toDateString(),
            'pic_user_id' => $actor->id,
            'notes' => 'Seeder: sesi contoh GU-01 (draft — siap input qty fisik)',
        ], $actor);

        // Isi sebagian agar selisih terlihat di preview (belum submit)
        $line = $opname->lines()->with('rack')->get()->first(
            fn ($item) => $item->rack?->code === 'GENERAL',
        ) ?? $opname->lines()->first();

        if ($line) {
            $service->updateCounts($opname, [[
                'id' => $line->id,
                'qty_counted' => max(0, (float) $line->qty_system - 2),
                'notes' => 'Seeder: contoh selisih -2',
            ]], $actor);
        }
    }
}
