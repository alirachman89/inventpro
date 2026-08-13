<?php

namespace Database\Seeders;

use App\Models\AssetUnit;
use App\Models\BorrowRequest;
use App\Models\Client;
use App\Models\User;
use App\Services\BorrowService;
use Illuminate\Database\Seeder;

class BorrowRequestSeeder extends Seeder
{
    public function run(): void
    {
        if (BorrowRequest::query()->exists()) {
            return;
        }

        $service = app(BorrowService::class);
        $warehouse = User::query()->where('email', 'warehouse@inventpro.local')->firstOrFail();
        $clientA = Client::query()->where('code', 'CLI-A')->firstOrFail();
        $asset = AssetUnit::query()
            ->where('asset_tag', 'AST-FRAME-001')
            ->where('status', 'available')
            ->firstOrFail();

        $service->create([
            'borrower_user_id' => $warehouse->id,
            'client_id' => $clientA->id,
            'borrow_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'purpose' => 'Scaffold Holding dipinjam Karyawan A untuk Client A',
            'notes' => 'Seeder Phase 10 — draft siap diajukan',
            'lines' => [[
                'asset_unit_id' => $asset->id,
            ]],
        ], $warehouse);
    }
}
