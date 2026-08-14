<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeAssetStatusRequest;
use App\Http\Requests\Admin\StoreAssetUnitRequest;
use App\Models\AssetUnit;
use App\Models\Client;
use App\Models\Item;
use App\Services\AssetLookupService;
use App\Services\AssetStatusService;
use App\Services\AuditLogger;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class AssetUnitController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StockService $stockService,
        private readonly AssetStatusService $assetStatusService,
        private readonly AssetLookupService $assetLookup,
    ) {}

    public function create(Item $item): Response|RedirectResponse
    {
        if (! $item->isAsset()) {
            return redirect()
                ->route('admin.items.show', $item)
                ->with('error', 'Unit asset hanya untuk tipe barang asset.');
        }

        $item->loadMissing('uom:id,code,name');

        return Inertia::render('Admin/AssetUnits/Form', [
            'item' => $item->only(['id', 'sku', 'name']),
            'statuses' => AssetUnit::MANUAL_STATUSES,
            'conditions' => AssetUnit::CONDITIONS,
            'locations' => $this->locationOptions(),
            'clients' => Client::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreAssetUnitRequest $request, Item $item): RedirectResponse
    {
        if (! $item->isAsset()) {
            return back()->with('error', 'Unit asset hanya untuk tipe barang asset.');
        }

        $data = $request->validated();

        try {
            $rack = $this->stockService->resolveRack($data['location_id'], $data['rack_id'] ?? null);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['rack_id' => $e->getMessage()]);
        }

        $asset = $item->assetUnits()->create([
            ...$data,
            'rack_id' => $rack->id,
        ]);

        $asset->statusHistories()->create([
            'from_status' => null,
            'to_status' => $asset->status,
            'changed_by' => $request->user()?->id,
            'notes' => 'Unit asset dibuat',
            'created_at' => now(),
        ]);

        $this->auditLogger->log(
            action: 'created',
            module: 'asset_units',
            description: "Unit asset {$asset->asset_tag} dibuat",
            auditable: $asset,
            newValues: $asset->only(['asset_tag', 'serial_number', 'status', 'location_id', 'rack_id']),
        );

        return redirect()
            ->route('admin.asset-units.show', $asset)
            ->with('success', 'Unit asset berhasil dibuat.');
    }

    public function show(AssetUnit $assetUnit): Response
    {
        $assetUnit->load([
            'item:id,sku,name',
            'location:id,code,name',
            'rack:id,code,label',
            'holder:id,name',
            'client:id,code,name',
            'statusHistories.changer:id,name',
        ]);

        return Inertia::render('Admin/AssetUnits/Show', [
            'asset' => [
                'id' => $assetUnit->id,
                'asset_tag' => $assetUnit->asset_tag,
                'serial_number' => $assetUnit->serial_number,
                'status' => $assetUnit->status,
                'condition' => $assetUnit->condition,
                'notes' => $assetUnit->notes,
                'item' => $assetUnit->item?->only(['id', 'sku', 'name']),
                'location' => $assetUnit->location?->only(['code', 'name']),
                'rack' => $assetUnit->rack?->only(['code', 'label']),
                'holder' => $assetUnit->holder?->name,
                'client' => $assetUnit->client?->only(['id', 'code', 'name']),
            ],
            'histories' => $assetUnit->statusHistories->map(fn ($history) => [
                'id' => $history->id,
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'notes' => $history->notes,
                'changed_by' => $history->changer?->name,
                'created_at' => $history->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ]),
            'manualStatuses' => AssetUnit::MANUAL_STATUSES,
            'statusLabels' => [
                'available' => 'Tersedia',
                'reserved' => 'Reservasi',
                'borrowed' => 'Dipinjam',
                'in_transit' => 'Dalam perjalanan',
                'maintenance' => 'Perawatan',
                'damaged' => 'Rusak',
                'quarantine' => 'Karantina',
                'lost' => 'Hilang',
                'disposed' => 'Dihapusbukukan',
            ],
        ]);
    }

    public function changeStatus(ChangeAssetStatusRequest $request, AssetUnit $assetUnit): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->assetStatusService->changeStatus(
                asset: $assetUnit,
                toStatus: $data['status'],
                actor: $request->user(),
                notes: $data['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        $this->auditLogger->log(
            action: 'updated',
            module: 'asset_units',
            description: "Status asset {$assetUnit->asset_tag} → {$data['status']}",
            auditable: $assetUnit,
            newValues: $data,
        );

        return back()->with('success', 'Status asset diperbarui.');
    }

    public function lookup(Request $request): JsonResponse
    {
        $code = $request->string('code')->toString();
        $availableOnly = $request->boolean('available_only');
        $asset = $this->assetLookup->lookup($code, $availableOnly);

        if (! $asset) {
            return response()->json([
                'found' => false,
                'message' => 'Asset tag / serial tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'asset' => $asset,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function locationOptions(): array
    {
        return \App\Models\Location::query()
            ->with(['racks' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_default')->orderBy('code')])
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn ($location) => [
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
}
