<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Http\Requests\Admin\StoreItemRequest;
use App\Http\Requests\Admin\UpdateItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Location;
use App\Models\Unit;
use App\Services\AuditLogger;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ItemController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StockService $stockService,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $locationId = $request->string('location_id')->toString();
        $rackQuery = $request->string('rack')->toString();

        $items = Item::query()
            ->with(['category:id,name', 'uom:id,code,name', 'stocks'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($locationId !== '', function ($query) use ($locationId, $rackQuery) {
                $query->whereHas('stocks', function ($q) use ($locationId, $rackQuery) {
                    $q->where('location_id', $locationId);
                    if ($rackQuery !== '') {
                        $q->whereHas('rack', function ($rq) use ($rackQuery) {
                            $rq->where('code', 'like', "%{$rackQuery}%")
                                ->orWhere('label', 'like', "%{$rackQuery}%");
                        });
                    }
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Item $item) {
                $available = $item->totalAvailable();

                return [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'item_type' => $item->item_type,
                    'category' => $item->category?->name,
                    'uom' => $item->uom ? "{$item->uom->code} — {$item->uom->name}" : null,
                    'min_stock' => (float) $item->min_stock,
                    'qty_available' => $available,
                    'is_low_stock' => $available < (float) $item->min_stock,
                    'is_active' => $item->is_active,
                ];
            });

        return Inertia::render('Admin/Items/Index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'location_id' => $locationId,
                'rack' => $rackQuery,
            ],
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Items/Form', [
            'item' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = Item::query()->create($request->validated());

        $this->auditLogger->log(
            action: 'created',
            module: 'items',
            description: "Barang {$item->sku} dibuat",
            auditable: $item,
            newValues: $item->only(['sku', 'name', 'item_type', 'uom_id', 'category_id', 'min_stock', 'is_active']),
        );

        return redirect()
            ->route('admin.items.show', $item)
            ->with('success', 'Barang berhasil dibuat.');
    }

    public function show(Request $request, Item $item): Response
    {
        $locationId = $request->string('location_id')->toString();
        $rackQuery = $request->string('rack')->toString();

        $item->load(['category:id,name', 'uom:id,code,name']);

        $stocks = ItemStock::query()
            ->with(['location:id,code,name', 'rack:id,code,name,label'])
            ->where('item_id', $item->id)
            ->when($locationId !== '', fn ($q) => $q->where('location_id', $locationId))
            ->when($rackQuery !== '', function ($q) use ($rackQuery) {
                $q->whereHas('rack', function ($rq) use ($rackQuery) {
                    $rq->where('code', 'like', "%{$rackQuery}%")
                        ->orWhere('label', 'like', "%{$rackQuery}%")
                        ->orWhere('name', 'like', "%{$rackQuery}%");
                });
            })
            ->get()
            ->sortBy(fn (ItemStock $stock) => [
                $stock->location?->code,
                $stock->rack?->code,
            ])
            ->values()
            ->map(fn (ItemStock $stock) => [
                'id' => $stock->id,
                'location' => $stock->location?->only(['id', 'code', 'name']),
                'rack' => $stock->rack?->only(['id', 'code', 'name', 'label']),
                'condition' => $stock->condition,
                'qty_on_hand' => (float) $stock->qty_on_hand,
                'qty_reserved' => (float) $stock->qty_reserved,
                'qty_available' => $stock->qty_available,
            ]);

        $assetUnits = $item->assetUnits()
            ->with(['location:id,code,name', 'rack:id,code,label', 'holder:id,name'])
            ->orderBy('asset_tag')
            ->get()
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'asset_tag' => $asset->asset_tag,
                'serial_number' => $asset->serial_number,
                'status' => $asset->status,
                'condition' => $asset->condition,
                'location' => $asset->location?->only(['code', 'name']),
                'rack' => $asset->rack?->only(['code', 'label']),
                'holder' => $asset->holder?->name,
            ]);

        $ledgers = $item->ledgers()
            ->with(['location:id,code,name', 'rack:id,code,label', 'creator:id,name'])
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($ledger) => [
                'id' => $ledger->id,
                'movement_type' => $ledger->movement_type,
                'qty_delta' => (float) $ledger->qty_delta,
                'qty_after' => (float) $ledger->qty_after,
                'location' => $ledger->location?->code,
                'rack' => $ledger->rack?->only(['code', 'label']),
                'notes' => $ledger->notes,
                'created_by' => $ledger->creator?->name,
                'created_at' => $ledger->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ]);

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

        return Inertia::render('Admin/Items/Show', [
            'item' => [
                'id' => $item->id,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'name' => $item->name,
                'item_type' => $item->item_type,
                'is_serialized' => $item->is_serialized,
                'description' => $item->description,
                'min_stock' => (float) $item->min_stock,
                'is_active' => $item->is_active,
                'category' => $item->category?->name,
                'uom' => $item->uom ? "{$item->uom->code} — {$item->uom->name}" : null,
                'qty_available' => (float) $stocks->sum('qty_available'),
                'is_low_stock' => (float) $stocks->sum('qty_available') < (float) $item->min_stock,
            ],
            'stocks' => $stocks,
            'assetUnits' => $assetUnits,
            'ledgers' => $ledgers,
            'locations' => $locations,
            'filters' => [
                'location_id' => $locationId,
                'rack' => $rackQuery,
            ],
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function edit(Item $item): Response
    {
        return Inertia::render('Admin/Items/Form', [
            'item' => $item->only([
                'id', 'sku', 'barcode', 'name', 'item_type', 'is_serialized',
                'category_id', 'uom_id', 'description', 'min_stock', 'is_active',
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $old = $item->only(['sku', 'name', 'item_type', 'uom_id', 'category_id', 'min_stock', 'is_active']);
        $item->update($request->validated());

        $this->auditLogger->log(
            action: 'updated',
            module: 'items',
            description: "Barang {$item->sku} diperbarui",
            auditable: $item,
            oldValues: $old,
            newValues: $item->only(['sku', 'name', 'item_type', 'uom_id', 'category_id', 'min_stock', 'is_active']),
        );

        return redirect()
            ->route('admin.items.show', $item)
            ->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        $old = $item->only(['sku', 'name', 'item_type']);
        $sku = $item->sku;
        $item->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'items',
            description: "Barang {$sku} dihapus",
            oldValues: $old,
        );

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Barang berhasil dihapus.');
    }

    public function adjustStock(AdjustStockRequest $request, Item $item): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->stockService->adjust(
                item: $item,
                locationId: $data['location_id'],
                rackId: $data['rack_id'] ?? null,
                qtyDelta: (float) $data['qty'],
                movementType: ((float) $data['qty']) >= 0 ? 'adjust_in' : 'adjust_out',
                actor: $request->user(),
                notes: $data['notes'] ?? null,
                condition: $data['condition'],
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['qty' => $e->getMessage()]);
        }

        $this->auditLogger->log(
            action: 'updated',
            module: 'item_stocks',
            description: "Penyesuaian stok {$item->sku}: {$data['qty']}",
            auditable: $item,
            newValues: $data,
        );

        return back()->with('success', 'Stok berhasil disesuaikan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'units' => Unit::query()->where('is_active', true)->orderBy('sort_order')->orderBy('code')->get(['id', 'code', 'name']),
            'types' => Item::TYPES,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'available' => 'Tersedia',
            'reserved' => 'Reservasi',
            'borrowed' => 'Dipinjam',
            'in_transit' => 'Dalam perjalanan',
            'maintenance' => 'Perawatan',
            'damaged' => 'Rusak',
            'quarantine' => 'Karantina',
            'lost' => 'Hilang',
            'disposed' => 'Dihapusbukukan',
        ];
    }
}
