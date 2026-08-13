<?php

namespace Database\Seeders;

use App\Models\ApprovalRequest;
use App\Models\AssetUnit;
use App\Models\BorrowRequest;
use App\Models\Category;
use App\Models\Client;
use App\Models\Item;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\Rack;
use App\Models\StockLedger;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ApprovalEngine;
use App\Services\BorrowService;
use App\Services\DocumentNumberService;
use App\Services\GoodsReceiptService;
use App\Services\StockMovementService;
use App\Services\StockOpnameService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo data agar dashboard/antrian kerja terlihat hidup (bukan hanya 1 bar hari ini).
 * Jalankan setelah seeder fase operasional.
 */
class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        if (Item::query()->where('sku', 'BAUT-M12')->exists()) {
            return;
        }

        $warehouse = User::query()->where('email', 'warehouse@inventpro.local')->firstOrFail();
        $purchasing = User::query()->where('email', 'purchasing@inventpro.local')->firstOrFail();
        $approver = User::query()->where('email', 'approver@inventpro.local')->firstOrFail();
        $admin = User::query()->where('email', 'admin@inventpro.local')->firstOrFail();

        $gudangUtama = Location::query()->where('code', 'GU-01')->firstOrFail();
        $siteB = Location::query()->where('code', 'SITE-B')->firstOrFail();
        $clientA = Client::query()->where('code', 'CLI-A')->firstOrFail();
        $clientB = Client::query()->where('code', 'CLI-B')->firstOrFail();
        $vendorFast = Vendor::query()->where('code', 'VND-FAST')->firstOrFail();
        $vendorPpe = Vendor::query()->where('code', 'VND-PPE')->firstOrFail();

        $rack = fn (Location $location, string $code) => Rack::query()
            ->where('location_id', $location->id)
            ->where('code', $code)
            ->firstOrFail();

        $items = $this->seedExtraItems($gudangUtama, $siteB, $rack);
        $assets = $this->seedExtraAssets($gudangUtama, $siteB, $rack, $clientB, $admin);

        $this->seedHistoricalMutations(
            warehouse: $warehouse,
            clientA: $clientA,
            gudangUtama: $gudangUtama,
            siteB: $siteB,
            rack: $rack,
            items: $items,
        );

        $this->seedPurchasePipeline(
            purchasing: $purchasing,
            approver: $approver,
            warehouse: $warehouse,
            vendorFast: $vendorFast,
            vendorPpe: $vendorPpe,
            gudangUtama: $gudangUtama,
            rack: $rack,
            items: $items,
        );

        $this->seedBorrowPipeline(
            warehouse: $warehouse,
            approver: $approver,
            admin: $admin,
            clientA: $clientA,
            clientB: $clientB,
            assets: $assets,
        );

        $this->seedOpnamePipeline(
            warehouse: $warehouse,
            approver: $approver,
            siteB: $siteB,
        );
    }

    /**
     * @param  callable(Location, string): Rack  $rack
     * @return array{baut: Item, mur: Item, helm: Item, klem: Item}
     */
    private function seedExtraItems(Location $gudangUtama, Location $siteB, callable $rack): array
    {
        $stock = app(StockService::class);
        $pcs = Unit::query()->where('code', 'pcs')->firstOrFail();
        $fastener = Category::query()->where('code', 'FASTENER')->first();
        $ppe = Category::query()->where('code', 'PPE')->first();

        $baut = Item::query()->create([
            'sku' => 'BAUT-M12',
            'name' => 'Baut M12',
            'barcode' => '8991003003003',
            'item_type' => 'consumable',
            'is_serialized' => false,
            'category_id' => $fastener?->id,
            'uom_id' => $pcs->id,
            'description' => 'Demo dashboard — mutasi aktif',
            'min_stock' => 40,
            'is_active' => true,
        ]);

        $mur = Item::query()->create([
            'sku' => 'MUR-M12',
            'name' => 'Mur M12',
            'barcode' => '8991003003004',
            'item_type' => 'consumable',
            'is_serialized' => false,
            'category_id' => $fastener?->id,
            'uom_id' => $pcs->id,
            'description' => 'Demo dashboard — low stock alert',
            'min_stock' => 80,
            'is_active' => true,
        ]);

        $helm = Item::query()->create([
            'sku' => 'HELM-YEL-01',
            'name' => 'Helm Safety Kuning',
            'barcode' => '8991004004004',
            'item_type' => 'consumable',
            'is_serialized' => false,
            'category_id' => $ppe?->id,
            'uom_id' => $pcs->id,
            'description' => 'Demo dashboard — PPE',
            'min_stock' => 10,
            'is_active' => true,
        ]);

        $positions = [
            [$baut, $gudangUtama, 'GENERAL', 200],
            [$baut, $gudangUtama, 'A-01', 50],
            [$baut, $siteB, 'RACK-01', 30],
            [$mur, $gudangUtama, 'GENERAL', 25], // di bawah min_stock 80
            [$helm, $gudangUtama, 'B-02', 18],
            [$helm, $siteB, 'RACK-01', 6],
        ];

        foreach ($positions as [$item, $location, $code, $qty]) {
            $stock->adjust(
                item: $item,
                locationId: $location->id,
                rackId: $rack($location, $code)->id,
                qtyDelta: $qty,
                movementType: 'seed',
                notes: 'DemoDashboardSeeder stok awal',
            );
        }

        // Sebar ledger seed ke ~25 hari ke belakang agar chart tidak hanya hari ini
        $seedLedgers = StockLedger::query()
            ->where('movement_type', 'seed')
            ->whereIn('item_id', [$baut->id, $mur->id, $helm->id])
            ->orderBy('created_at')
            ->get();

        foreach ($seedLedgers as $index => $ledger) {
            $at = now()->subDays(28 - ($index % 7))->setTime(8, 10 + $index);
            $ledger->forceFill(['created_at' => $at])->save();
        }

        return [
            'baut' => $baut,
            'mur' => $mur,
            'helm' => $helm,
            'klem' => Item::query()->where('sku', 'KLEM-001')->firstOrFail(),
        ];
    }

    /**
     * @param  callable(Location, string): Rack  $rack
     * @return array{overdue: AssetUnit, checkout: AssetUnit, pending: AssetUnit}
     */
    private function seedExtraAssets(
        Location $gudangUtama,
        Location $siteB,
        callable $rack,
        Client $clientB,
        User $admin,
    ): array {
        $frame = Item::query()->where('sku', 'SCF-FRAME-01')->firstOrFail();

        $defs = [
            ['AST-FRAME-004', 'SN-FR-004', 'damaged', $gudangUtama, 'GENERAL', null, null],
            ['AST-FRAME-005', 'SN-FR-005', 'quarantine', $gudangUtama, 'A-01', null, null],
            ['AST-FRAME-006', 'SN-FR-006', 'in_transit', $siteB, 'RACK-01', null, null],
            ['AST-FRAME-007', 'SN-FR-007', 'available', $gudangUtama, 'GENERAL', null, null],
            ['AST-FRAME-008', 'SN-FR-008', 'available', $gudangUtama, 'GENERAL', null, null],
            ['AST-FRAME-009', 'SN-FR-009', 'available', $gudangUtama, 'B-02', null, null],
            ['AST-FRAME-010', 'SN-FR-010', 'borrowed', $siteB, 'RACK-01', $clientB->id, $admin->id],
        ];

        $created = [];
        foreach ($defs as [$tag, $serial, $status, $location, $rackCode, $clientId, $holderId]) {
            $asset = AssetUnit::query()->create([
                'item_id' => $frame->id,
                'asset_tag' => $tag,
                'serial_number' => $serial,
                'status' => $status,
                'condition' => $status === 'damaged' ? 'damaged' : 'good',
                'location_id' => $location->id,
                'rack_id' => $rack($location, $rackCode)->id,
                'current_client_id' => $clientId,
                'current_holder_user_id' => $holderId,
                'notes' => 'DemoDashboardSeeder asset',
            ]);

            $asset->statusHistories()->create([
                'from_status' => null,
                'to_status' => $status,
                'notes' => 'DemoDashboardSeeder',
                'created_at' => now()->subDays(12),
            ]);

            $created[$tag] = $asset;
        }

        return [
            'overdue' => $created['AST-FRAME-007'],
            'checkout' => $created['AST-FRAME-008'],
            'pending' => $created['AST-FRAME-009'],
        ];
    }

    /**
     * @param  callable(Location, string): Rack  $rack
     * @param  array{baut: Item, mur: Item, helm: Item, klem: Item}  $items
     */
    private function seedHistoricalMutations(
        User $warehouse,
        Client $clientA,
        Location $gudangUtama,
        Location $siteB,
        callable $rack,
        array $items,
    ): void {
        $movements = app(StockMovementService::class);
        $guGeneral = $rack($gudangUtama, 'GENERAL');
        $guA01 = $rack($gudangUtama, 'A-01');
        $siteRack = $rack($siteB, 'RACK-01');

        $catalog = [
            $items['baut'],
            $items['klem'],
            $items['helm'],
            $items['mur'],
        ];

        for ($daysAgo = 27; $daysAgo >= 1; $daysAgo--) {
            $day = now()->subDays($daysAgo)->startOfDay();

            // Skip beberapa hari agar chart tidak "sempurna" artificial
            if ($daysAgo % 5 === 0) {
                continue;
            }

            $item = $catalog[$daysAgo % count($catalog)];
            $inQty = 8 + ($daysAgo % 7) * 2;
            $outQty = 3 + ($daysAgo % 5);

            $inAt = $day->copy()->setTime(9, 10 + ($daysAgo % 40));
            $in = $movements->create([
                'type' => 'in',
                'reason' => 'return',
                'movement_date' => $day->toDateString(),
                'notes' => 'DemoDashboardSeeder historical in',
                'lines' => [[
                    'item_id' => $item->id,
                    'qty' => $inQty,
                    'condition' => 'good',
                    'to_location_id' => $gudangUtama->id,
                    'to_rack_id' => $guGeneral->id,
                ]],
            ], $warehouse);
            $this->backdateMovement($in, $inAt);

            $outAt = $day->copy()->setTime(14, 20 + ($daysAgo % 30));
            $out = $movements->create([
                'type' => 'out',
                'reason' => 'issue_to_client',
                'movement_date' => $day->toDateString(),
                'client_id' => $clientA->id,
                'notes' => 'DemoDashboardSeeder historical out',
                'lines' => [[
                    'item_id' => $item->id,
                    'qty' => $outQty,
                    'condition' => 'good',
                    'from_location_id' => $gudangUtama->id,
                    'from_rack_id' => $guGeneral->id,
                ]],
            ], $warehouse);
            $this->backdateMovement($out, $outAt);

            if ($daysAgo % 7 === 1) {
                $transferAt = $day->copy()->setTime(16, 5);
                $transfer = $movements->create([
                    'type' => 'transfer',
                    'reason' => 'transfer',
                    'movement_date' => $day->toDateString(),
                    'notes' => 'DemoDashboardSeeder historical transfer',
                    'lines' => [[
                        'item_id' => $items['baut']->id,
                        'qty' => 2,
                        'condition' => 'good',
                        'from_location_id' => $gudangUtama->id,
                        'from_rack_id' => $guA01->id,
                        'to_location_id' => $siteB->id,
                        'to_rack_id' => $siteRack->id,
                    ]],
                ], $warehouse);
                $this->backdateMovement($transfer, $transferAt);
            }
        }
    }

    private function backdateMovement(StockMovement $movement, Carbon $at): void
    {
        $movement->forceFill([
            'created_at' => $at,
            'updated_at' => $at,
        ])->save();

        StockLedger::query()
            ->where('notes', "MOV {$movement->number}")
            ->update(['created_at' => $at]);
    }

    /**
     * @param  callable(Location, string): Rack  $rack
     * @param  array{baut: Item, mur: Item, helm: Item, klem: Item}  $items
     */
    private function seedPurchasePipeline(
        User $purchasing,
        User $approver,
        User $warehouse,
        Vendor $vendorFast,
        Vendor $vendorPpe,
        Location $gudangUtama,
        callable $rack,
        array $items,
    ): void {
        $numbers = app(DocumentNumberService::class);
        $approvals = app(ApprovalEngine::class);
        $gr = app(GoodsReceiptService::class);

        // PO submitted → antrian approval + alert
        $submitted = PurchaseOrder::query()->create([
            'number' => $numbers->next('prefix_po', 'PO'),
            'vendor_id' => $vendorPpe->id,
            'status' => 'draft',
            'order_date' => now()->subDays(1)->toDateString(),
            'notes' => 'DemoDashboardSeeder — menunggu approval',
            'created_by' => $purchasing->id,
            'total_amount' => 0,
        ]);
        $submitted->lines()->create([
            'item_id' => $items['helm']->id,
            'line_no' => 1,
            'qty_ordered' => 40,
            'qty_received' => 0,
            'unit_price' => 75000,
            'line_total' => 3000000,
        ]);
        $submitted->update(['total_amount' => 3000000]);
        $approvals->submit(PurchaseOrder::DOCUMENT_TYPE, $submitted, $purchasing, $submitted->number);

        // PO ordered + partial GR → work queue GR
        $partial = PurchaseOrder::query()->create([
            'number' => $numbers->next('prefix_po', 'PO'),
            'vendor_id' => $vendorFast->id,
            'status' => 'ordered',
            'order_date' => now()->subDays(5)->toDateString(),
            'notes' => 'DemoDashboardSeeder — partial GR',
            'created_by' => $purchasing->id,
            'submitted_at' => now()->subDays(5),
            'approved_at' => now()->subDays(4),
            'total_amount' => 0,
        ]);
        $partialLine = $partial->lines()->create([
            'item_id' => $items['baut']->id,
            'line_no' => 1,
            'qty_ordered' => 100,
            'qty_received' => 0,
            'unit_price' => 1500,
            'line_total' => 150000,
        ]);
        $partial->update(['total_amount' => 150000]);

        $receipt = $gr->receive($partial->fresh(['lines']), [
            'location_id' => $gudangUtama->id,
            'received_date' => now()->subDays(2)->toDateString(),
            'notes' => 'DemoDashboardSeeder partial receive',
            'lines' => [[
                'purchase_order_line_id' => $partialLine->id,
                'rack_id' => $rack($gudangUtama, 'GENERAL')->id,
                'qty_received' => 40,
            ]],
        ], $warehouse);

        $grAt = now()->subDays(2)->setTime(11, 30);
        $receipt->forceFill(['created_at' => $grAt, 'updated_at' => $grAt])->save();
        StockLedger::query()
            ->where('notes', 'like', "GR {$receipt->number}%")
            ->update(['created_at' => $grAt]);
    }

    /**
     * @param  array{overdue: AssetUnit, checkout: AssetUnit, pending: AssetUnit}  $assets
     */
    private function seedBorrowPipeline(
        User $warehouse,
        User $approver,
        User $admin,
        Client $clientA,
        Client $clientB,
        array $assets,
    ): void {
        $borrows = app(BorrowService::class);
        $approvals = app(ApprovalEngine::class);

        // Overdue checked out
        $overdue = $borrows->create([
            'borrower_user_id' => $admin->id,
            'client_id' => $clientA->id,
            'borrow_date' => now()->subDays(14)->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(),
            'purpose' => 'Demo overdue — frame di Client A',
            'notes' => 'DemoDashboardSeeder overdue',
            'lines' => [['asset_unit_id' => $assets['overdue']->id]],
        ], $warehouse);
        $approvals->submit(BorrowRequest::DOCUMENT_TYPE, $overdue, $warehouse, $overdue->number);
        $pending = ApprovalRequest::query()
            ->where('document_type', BorrowRequest::DOCUMENT_TYPE)
            ->where('document_id', $overdue->id)
            ->where('status', 'pending')
            ->firstOrFail();
        $approvals->approve($pending, $approver, 'Demo approve overdue borrow');
        $borrows->checkout($overdue->fresh(), $warehouse);
        $overdue->fresh()->forceFill([
            'checked_out_at' => now()->subDays(14),
            'borrow_date' => now()->subDays(14)->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(),
        ])->save();

        // Approved — menunggu checkout (work queue)
        $ready = $borrows->create([
            'borrower_user_id' => $warehouse->id,
            'client_id' => $clientB->id,
            'borrow_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'purpose' => 'Demo — siap checkout',
            'notes' => 'DemoDashboardSeeder approved checkout',
            'lines' => [['asset_unit_id' => $assets['checkout']->id]],
        ], $warehouse);
        $approvals->submit(BorrowRequest::DOCUMENT_TYPE, $ready, $warehouse, $ready->number);
        $readyPending = ApprovalRequest::query()
            ->where('document_type', BorrowRequest::DOCUMENT_TYPE)
            ->where('document_id', $ready->id)
            ->where('status', 'pending')
            ->firstOrFail();
        $approvals->approve($readyPending, $approver, 'Demo approve checkout queue');

        // Submitted — menunggu approval
        $waiting = $borrows->create([
            'borrower_user_id' => $admin->id,
            'client_id' => $clientA->id,
            'borrow_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'purpose' => 'Demo — menunggu persetujuan',
            'notes' => 'DemoDashboardSeeder pending approval',
            'lines' => [['asset_unit_id' => $assets['pending']->id]],
        ], $warehouse);
        $approvals->submit(BorrowRequest::DOCUMENT_TYPE, $waiting, $warehouse, $waiting->number);
    }

    private function seedOpnamePipeline(User $warehouse, User $approver, Location $siteB): void
    {
        $opnames = app(StockOpnameService::class);
        $approvals = app(ApprovalEngine::class);

        // Submitted dengan selisih → alert + approval queue
        $submitted = $opnames->create([
            'location_id' => $siteB->id,
            'opname_date' => now()->subDay()->toDateString(),
            'pic_user_id' => $warehouse->id,
            'notes' => 'DemoDashboardSeeder — menunggu approval selisih',
        ], $warehouse);

        $submittedLines = $submitted->lines()->get()->map(function ($line, $index) {
            $system = (float) $line->qty_system;
            $counted = $index === 0 ? max(0, $system - 1) : $system;

            return [
                'id' => $line->id,
                'qty_counted' => $counted,
                'notes' => $index === 0 ? 'Demo selisih -1' : null,
            ];
        })->all();

        if ($submittedLines === []) {
            return;
        }

        $opnames->updateCounts($submitted, $submittedLines, $warehouse);
        $opnames->submit($submitted->fresh(), $warehouse);

        // Approved siap posting → work queue
        $approved = $opnames->create([
            'location_id' => $siteB->id,
            'opname_date' => now()->toDateString(),
            'pic_user_id' => $warehouse->id,
            'notes' => 'DemoDashboardSeeder — siap posting',
        ], $warehouse);

        $approvedLines = $approved->lines()->get()->map(function ($line, $index) {
            $system = (float) $line->qty_system;
            $counted = $index === 0 ? $system + 2 : $system;

            return [
                'id' => $line->id,
                'qty_counted' => $counted,
                'notes' => $index === 0 ? 'Demo selisih +2' : null,
            ];
        })->all();

        if ($approvedLines === []) {
            return;
        }

        $opnames->updateCounts($approved, $approvedLines, $warehouse);
        $opnames->submit($approved->fresh(), $warehouse);

        $pending = ApprovalRequest::query()
            ->where('document_type', StockOpname::DOCUMENT_TYPE)
            ->where('document_id', $approved->id)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            $approvals->approve($pending, $approver, 'Demo approve opname for posting queue');
        }
    }
}
