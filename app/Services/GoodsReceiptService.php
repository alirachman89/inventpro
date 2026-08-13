<?php

namespace App\Services;

use App\Models\AssetUnit;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly DocumentNumberService $documentNumbers,
        private readonly AuditLogger $auditLogger,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array{
     *   location_id: string,
     *   received_date: string,
     *   notes?: ?string,
     *   lines: list<array{purchase_order_line_id: string, rack_id?: ?string, qty_received: float|int|string, notes?: ?string}>
     * }  $data
     */
    public function receive(PurchaseOrder $po, array $data, User $actor): GoodsReceipt
    {
        if (! $po->canReceive()) {
            throw ValidationException::withMessages([
                'purchase_order' => 'PO belum siap diterima (harus berstatus ordered / partially_received).',
            ]);
        }

        $linesInput = collect($data['lines'] ?? [])
            ->filter(fn ($line) => (float) ($line['qty_received'] ?? 0) > 0)
            ->values();

        if ($linesInput->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Minimal satu baris penerimaan dengan qty > 0.',
            ]);
        }

        return DB::transaction(function () use ($po, $data, $actor, $linesInput) {
            $po = PurchaseOrder::query()->whereKey($po->id)->lockForUpdate()->with('lines.item')->firstOrFail();

            $receipt = GoodsReceipt::query()->create([
                'number' => $this->documentNumbers->next('prefix_gr', 'GR'),
                'purchase_order_id' => $po->id,
                'location_id' => $data['location_id'],
                'received_date' => $data['received_date'],
                'notes' => $data['notes'] ?? null,
                'received_by' => $actor->id,
            ]);

            foreach ($linesInput as $input) {
                /** @var PurchaseOrderLine|null $poLine */
                $poLine = $po->lines->firstWhere('id', $input['purchase_order_line_id']);

                if (! $poLine) {
                    throw ValidationException::withMessages([
                        'lines' => 'Baris PO tidak valid.',
                    ]);
                }

                $qty = (float) $input['qty_received'];
                $outstanding = $poLine->qtyOutstanding();

                if ($qty > $outstanding + 0.0001) {
                    throw ValidationException::withMessages([
                        'lines' => "Qty penerimaan melebihi sisa untuk item {$poLine->item?->sku} (sisa {$outstanding}).",
                    ]);
                }

                $rack = $this->stockService->resolveRack(
                    $data['location_id'],
                    $input['rack_id'] ?? null,
                );

                $receiptLine = $receipt->lines()->create([
                    'purchase_order_line_id' => $poLine->id,
                    'item_id' => $poLine->item_id,
                    'rack_id' => $rack->id,
                    'qty_received' => $qty,
                    'notes' => $input['notes'] ?? null,
                ]);

                $item = $poLine->item;

                if ($item?->is_serialized || $item?->item_type === 'asset') {
                    $this->createAssetUnits($item, $data['location_id'], $rack->id, (int) round($qty), $receipt);
                } else {
                    $this->stockService->adjust(
                        item: $item,
                        locationId: $data['location_id'],
                        rackId: $rack->id,
                        qtyDelta: $qty,
                        movementType: 'goods_receipt',
                        actor: $actor,
                        notes: "GR {$receipt->number}",
                        referenceType: GoodsReceipt::class,
                        referenceId: $receipt->id,
                    );
                }

                $poLine->update([
                    'qty_received' => (float) $poLine->qty_received + $qty,
                ]);

                unset($receiptLine);
            }

            $po->refreshReceiveStatus();

            $this->auditLogger->log(
                action: 'created',
                module: 'goods_receipts',
                description: "GR {$receipt->number} untuk PO {$po->number}",
                auditable: $receipt,
                newValues: [
                    'purchase_order_id' => $po->id,
                    'location_id' => $receipt->location_id,
                ],
                actor: $actor,
            );

            if ($po->created_by && $po->created_by !== $actor->id) {
                $this->notifications->sendToUsers(
                    [$po->created_by],
                    'goods_receipt',
                    'Barang PO diterima',
                    "PO {$po->number} menerima GR {$receipt->number}.",
                    route('admin.purchase-orders.show', $po->id),
                );
            }

            return $receipt->fresh(['lines.item', 'lines.rack', 'location']);
        });
    }

    private function createAssetUnits(Item $item, string $locationId, string $rackId, int $qty, GoodsReceipt $receipt): void
    {
        for ($i = 1; $i <= $qty; $i++) {
            $tag = sprintf('%s-%s-%02d', $item->sku, $receipt->number, $i);
            $tag = strtoupper(substr(preg_replace('/[^A-Z0-9\-]/', '', $tag) ?? $tag, 0, 64));

            $asset = AssetUnit::query()->create([
                'item_id' => $item->id,
                'asset_tag' => $tag,
                'serial_number' => null,
                'status' => 'available',
                'condition' => 'good',
                'location_id' => $locationId,
                'rack_id' => $rackId,
                'notes' => "Dari GR {$receipt->number}",
            ]);

            $asset->statusHistories()->create([
                'from_status' => null,
                'to_status' => 'available',
                'notes' => "Dibuat dari GR {$receipt->number}",
                'created_at' => now(),
            ]);
        }
    }
}
