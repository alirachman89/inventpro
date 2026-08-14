<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGoodsReceiptRequest;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Services\GoodsReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GoodsReceiptController extends Controller
{
    public function __construct(private readonly GoodsReceiptService $goodsReceiptService) {}

    public function create(PurchaseOrder $purchaseOrder): Response|RedirectResponse
    {
        if (! $purchaseOrder->canReceive()) {
            return redirect()
                ->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('error', 'PO belum siap untuk Goods Receipt.');
        }

        $purchaseOrder->load(['vendor:id,code,name', 'lines.item:id,sku,barcode,name,item_type,is_serialized']);

        $locations = Location::query()
            ->with(['racks' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_default')->orderBy('code')])
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Location $location) => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'racks' => $location->racks->map(fn ($rack) => [
                    'id' => $rack->id,
                    'code' => $rack->code,
                    'label' => $rack->label,
                    'is_default' => $rack->is_default,
                ]),
            ]);

        return Inertia::render('Admin/GoodsReceipts/Form', [
            'order' => [
                'id' => $purchaseOrder->id,
                'number' => $purchaseOrder->number,
                'vendor' => $purchaseOrder->vendor?->only(['code', 'name']),
                'lines' => $purchaseOrder->lines
                    ->filter(fn ($line) => $line->qtyOutstanding() > 0)
                    ->values()
                    ->map(fn ($line) => [
                        'id' => $line->id,
                        'item' => $line->item?->only(['sku', 'barcode', 'name', 'item_type', 'is_serialized']),
                        'qty_ordered' => (float) $line->qty_ordered,
                        'qty_received' => (float) $line->qty_received,
                        'qty_outstanding' => $line->qtyOutstanding(),
                    ]),
            ],
            'locations' => $locations,
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $receipt = $this->goodsReceiptService->receive(
                $purchaseOrder,
                $request->validated(),
                $request->user(),
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('admin.purchase-orders.show', $purchaseOrder)
            ->with('success', "Goods Receipt {$receipt->number} berhasil dicatat. Stok diperbarui.");
    }
}
