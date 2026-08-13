<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStockMovementRequest;
use App\Models\Client;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockMovement;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function __construct(private readonly StockMovementService $stockMovementService) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();

        $movements = StockMovement::query()
            ->with(['creator:id,name', 'client:id,code,name'])
            ->withCount('lines')
            ->when($search !== '', fn ($q) => $q->where('number', 'like', "%{$search}%"))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'number' => $movement->number,
                'type' => $movement->type,
                'reason' => $movement->reason,
                'movement_date' => $movement->movement_date?->format('Y-m-d'),
                'client' => $movement->client?->only(['code', 'name']),
                'lines_count' => $movement->lines_count,
                'creator' => $movement->creator?->name,
            ]);

        return Inertia::render('Admin/StockMovements/Index', [
            'movements' => $movements,
            'filters' => ['search' => $search, 'type' => $type],
            'types' => StockMovement::TYPES,
            'typeLabels' => $this->typeLabels(),
            'reasonLabels' => $this->reasonLabels(),
        ]);
    }

    public function create(Request $request): Response
    {
        $type = $request->string('type')->toString() ?: 'in';
        if (! in_array($type, StockMovement::TYPES, true)) {
            $type = 'in';
        }

        return Inertia::render('Admin/StockMovements/Form', [
            'defaultType' => $type,
            'types' => StockMovement::TYPES,
            'typeLabels' => $this->typeLabels(),
            'reasonsByType' => [
                'in' => StockMovement::REASONS_IN,
                'out' => StockMovement::REASONS_OUT,
                'transfer' => StockMovement::REASONS_TRANSFER,
            ],
            'reasonLabels' => $this->reasonLabels(),
            'items' => Item::query()
                ->where('is_active', true)
                ->where('item_type', 'consumable')
                ->orderBy('name')
                ->get(['id', 'sku', 'name']),
            'locations' => $this->locationOptions(),
            'clients' => Client::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        try {
            $movement = $this->stockMovementService->create(
                $request->validated(),
                $request->user(),
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('admin.stock-movements.show', $movement)
            ->with('success', "Mutasi {$movement->number} berhasil dicatat.");
    }

    public function show(StockMovement $stockMovement): Response
    {
        $stockMovement->load([
            'creator:id,name',
            'client:id,code,name',
            'lines.item:id,sku,name',
            'lines.fromLocation:id,code,name',
            'lines.fromRack:id,code,label',
            'lines.toLocation:id,code,name',
            'lines.toRack:id,code,label',
            'lines.assetUnit:id,asset_tag',
        ]);

        return Inertia::render('Admin/StockMovements/Show', [
            'movement' => [
                'id' => $stockMovement->id,
                'number' => $stockMovement->number,
                'type' => $stockMovement->type,
                'reason' => $stockMovement->reason,
                'movement_date' => $stockMovement->movement_date?->format('Y-m-d'),
                'notes' => $stockMovement->notes,
                'client' => $stockMovement->client?->only(['code', 'name']),
                'creator' => $stockMovement->creator?->name,
                'lines' => $stockMovement->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'item' => $line->item?->only(['sku', 'name']),
                    'qty' => (float) $line->qty,
                    'condition' => $line->condition,
                    'from_location' => $line->fromLocation?->only(['code', 'name']),
                    'from_rack' => $line->fromRack?->only(['code', 'label']),
                    'to_location' => $line->toLocation?->only(['code', 'name']),
                    'to_rack' => $line->toRack?->only(['code', 'label']),
                    'asset_tag' => $line->assetUnit?->asset_tag,
                    'notes' => $line->notes,
                ]),
            ],
            'typeLabels' => $this->typeLabels(),
            'reasonLabels' => $this->reasonLabels(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function locationOptions(): array
    {
        return Location::query()
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
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function typeLabels(): array
    {
        return [
            'in' => 'Stock In',
            'out' => 'Stock Out',
            'transfer' => 'Transfer',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function reasonLabels(): array
    {
        return [
            'return' => 'Return',
            'adjustment_in' => 'Adjustment (+)',
            'issue' => 'Issue',
            'issue_to_client' => 'Issue ke Client',
            'damage' => 'Damage / rusak',
            'transfer' => 'Transfer antar lokasi/rak',
            'other' => 'Lainnya',
        ];
    }
}
