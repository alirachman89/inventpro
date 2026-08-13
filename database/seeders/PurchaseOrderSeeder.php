<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Services\DocumentNumberService;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $numbers = app(DocumentNumberService::class);
        $vendor = Vendor::query()->where('code', 'VND-FAST')->first()
            ?? Vendor::query()->first();
        $purchasing = User::query()->where('email', 'purchasing@inventpro.local')->first();
        $klem = Item::query()->where('sku', 'KLEM-001')->first();

        if (! $vendor || ! $klem || ! $purchasing) {
            return;
        }

        if (PurchaseOrder::query()->where('status', 'draft')->exists()) {
            return;
        }

        $draft = PurchaseOrder::query()->create([
            'number' => $numbers->next('prefix_po', 'PO'),
            'vendor_id' => $vendor->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'notes' => 'Seeder draft PO',
            'created_by' => $purchasing->id,
            'total_amount' => 0,
        ]);

        $draft->lines()->create([
            'item_id' => $klem->id,
            'line_no' => 1,
            'qty_ordered' => 100,
            'qty_received' => 0,
            'unit_price' => 2500,
            'line_total' => 250000,
        ]);
        $draft->update(['total_amount' => 250000]);

        $ordered = PurchaseOrder::query()->create([
            'number' => $numbers->next('prefix_po', 'PO'),
            'vendor_id' => $vendor->id,
            'status' => 'ordered',
            'order_date' => now()->subDays(2)->toDateString(),
            'notes' => 'Seeder PO siap GR — approve sudah lewat',
            'created_by' => $purchasing->id,
            'submitted_at' => now()->subDays(2),
            'approved_at' => now()->subDay(),
            'total_amount' => 0,
        ]);

        $ordered->lines()->create([
            'item_id' => $klem->id,
            'line_no' => 1,
            'qty_ordered' => 50,
            'qty_received' => 0,
            'unit_price' => 2500,
            'line_total' => 125000,
        ]);
        $ordered->update(['total_amount' => 125000]);
    }
}
