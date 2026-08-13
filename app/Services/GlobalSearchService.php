<?php

namespace App\Services;

use App\Models\AssetUnit;
use App\Models\BorrowRequest;
use App\Models\Client;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\Rack;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    /**
     * @return list<array{type: string, type_label: string, title: string, subtitle: ?string, href: ?string}>
     */
    public function search(string $query, User $user, int $limitPerType = 5): array
    {
        $q = trim($query);
        if (mb_strlen($q) < 2) {
            return [];
        }

        $like = '%'.$q.'%';
        $results = collect();

        if ($user->can('items.view')) {
            $results = $results->merge($this->searchItems($like, $limitPerType));
        }

        if ($user->can('asset_units.view')) {
            $results = $results->merge($this->searchAssets($like, $limitPerType));
        }

        if ($user->can('purchases.view')) {
            $results = $results->merge($this->searchPurchaseOrders($like, $limitPerType));
        }

        if ($user->can('goods_receipts.view') || $user->can('purchases.view')) {
            $results = $results->merge($this->searchGoodsReceipts($like, $limitPerType));
        }

        if ($user->can('stock_movements.view')) {
            $results = $results->merge($this->searchMovements($like, $limitPerType));
        }

        if ($user->can('stock_opnames.view')) {
            $results = $results->merge($this->searchOpnames($like, $limitPerType));
        }

        if ($user->can('borrows.view')) {
            $results = $results->merge($this->searchBorrows($like, $limitPerType));
        }

        if ($user->can('vendors.view')) {
            $results = $results->merge($this->searchVendors($like, $limitPerType));
        }

        if ($user->can('clients.view')) {
            $results = $results->merge($this->searchClients($like, $limitPerType));
        }

        if ($user->can('locations.view')) {
            $results = $results->merge($this->searchLocations($like, $limitPerType));
            $results = $results->merge($this->searchRacks($like, $limitPerType));
        }

        return $results->take(40)->values()->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchItems(string $like, int $limit): Collection
    {
        return Item::query()
            ->where(function ($q) use ($like) {
                $q->where('sku', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('barcode', 'like', $like);
            })
            ->orderBy('sku')
            ->limit($limit)
            ->get(['id', 'sku', 'name'])
            ->map(fn (Item $item) => [
                'type' => 'item',
                'type_label' => 'Barang',
                'title' => $item->sku.' — '.$item->name,
                'subtitle' => null,
                'href' => route('admin.items.show', $item->id),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchAssets(string $like, int $limit): Collection
    {
        return AssetUnit::query()
            ->with('item:id,sku,name')
            ->where(function ($q) use ($like) {
                $q->where('asset_tag', 'like', $like)
                    ->orWhere('serial_number', 'like', $like);
            })
            ->orderBy('asset_tag')
            ->limit($limit)
            ->get()
            ->map(fn (AssetUnit $unit) => [
                'type' => 'asset',
                'type_label' => 'Asset',
                'title' => $unit->asset_tag.($unit->serial_number ? ' / '.$unit->serial_number : ''),
                'subtitle' => ($unit->item?->sku ?? '').' · '.$unit->status,
                'href' => route('admin.asset-units.show', $unit->id),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchPurchaseOrders(string $like, int $limit): Collection
    {
        return PurchaseOrder::query()
            ->with('vendor:id,code,name')
            ->where('number', 'like', $like)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (PurchaseOrder $po) => [
                'type' => 'purchase_order',
                'type_label' => 'PO',
                'title' => $po->number,
                'subtitle' => ($po->vendor?->code ?? '').' · '.$po->status,
                'href' => route('admin.purchase-orders.show', $po->id),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchGoodsReceipts(string $like, int $limit): Collection
    {
        return GoodsReceipt::query()
            ->with('purchaseOrder:id,number')
            ->where('number', 'like', $like)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (GoodsReceipt $gr) => [
                'type' => 'goods_receipt',
                'type_label' => 'GR',
                'title' => $gr->number,
                'subtitle' => 'PO '.($gr->purchaseOrder?->number ?? '—'),
                'href' => $gr->purchase_order_id
                    ? route('admin.purchase-orders.show', $gr->purchase_order_id)
                    : null,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchMovements(string $like, int $limit): Collection
    {
        return StockMovement::query()
            ->where('number', 'like', $like)
            ->latest()
            ->limit($limit)
            ->get(['id', 'number', 'type', 'reason'])
            ->map(fn (StockMovement $mov) => [
                'type' => 'stock_movement',
                'type_label' => 'Mutasi',
                'title' => $mov->number,
                'subtitle' => $mov->type.' · '.$mov->reason,
                'href' => route('admin.stock-movements.show', $mov->id),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchOpnames(string $like, int $limit): Collection
    {
        return StockOpname::query()
            ->with('location:id,code,name')
            ->where('number', 'like', $like)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (StockOpname $opn) => [
                'type' => 'stock_opname',
                'type_label' => 'Opname',
                'title' => $opn->number,
                'subtitle' => ($opn->location?->code ?? '').' · '.$opn->status,
                'href' => route('admin.stock-opnames.show', $opn->id),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchBorrows(string $like, int $limit): Collection
    {
        return BorrowRequest::query()
            ->with(['borrower:id,name', 'client:id,code,name'])
            ->where('number', 'like', $like)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (BorrowRequest $borrow) => [
                'type' => 'borrow',
                'type_label' => 'Pinjam',
                'title' => $borrow->number,
                'subtitle' => ($borrow->borrower?->name ?? '').' → '.($borrow->client?->code ?? ''),
                'href' => route('admin.borrows.show', $borrow->id),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchVendors(string $like, int $limit): Collection
    {
        return Vendor::query()
            ->where(fn ($q) => $q->where('code', 'like', $like)->orWhere('name', 'like', $like))
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'name'])
            ->map(fn (Vendor $vendor) => [
                'type' => 'vendor',
                'type_label' => 'Vendor',
                'title' => $vendor->code.' — '.$vendor->name,
                'subtitle' => null,
                'href' => route('admin.vendors.show', $vendor->id),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchClients(string $like, int $limit): Collection
    {
        return Client::query()
            ->where(fn ($q) => $q->where('code', 'like', $like)->orWhere('name', 'like', $like))
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'name'])
            ->map(fn (Client $client) => [
                'type' => 'client',
                'type_label' => 'Client',
                'title' => $client->code.' — '.$client->name,
                'subtitle' => null,
                'href' => route('admin.clients.show', $client->id),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchLocations(string $like, int $limit): Collection
    {
        return Location::query()
            ->where(fn ($q) => $q->where('code', 'like', $like)->orWhere('name', 'like', $like))
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'name'])
            ->map(fn (Location $location) => [
                'type' => 'location',
                'type_label' => 'Lokasi',
                'title' => $location->code.' — '.$location->name,
                'subtitle' => null,
                'href' => route('admin.locations.show', $location->id),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchRacks(string $like, int $limit): Collection
    {
        return Rack::query()
            ->with('location:id,code,name')
            ->where(fn ($q) => $q->where('code', 'like', $like)->orWhere('label', 'like', $like))
            ->orderBy('code')
            ->limit($limit)
            ->get()
            ->map(fn (Rack $rack) => [
                'type' => 'rack',
                'type_label' => 'Rak',
                'title' => $rack->code.' — '.$rack->label,
                'subtitle' => $rack->location?->code,
                'href' => $rack->location_id
                    ? route('admin.locations.show', $rack->location_id)
                    : null,
            ]);
    }
}
