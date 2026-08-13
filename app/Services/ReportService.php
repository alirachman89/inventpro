<?php

namespace App\Services;

use App\Models\AssetUnit;
use App\Models\BorrowRequest;
use App\Models\Category;
use App\Models\Client;
use App\Models\GoodsReceipt;
use App\Models\ItemStock;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\Rack;
use App\Models\StockLedger;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>, meta: array<string, mixed>}
     */
    public function stock(Request $request): array
    {
        $query = ItemStock::query()
            ->with(['item.category:id,code,name', 'item.uom:id,code', 'location:id,code,name', 'rack:id,code,label'])
            ->where('qty_on_hand', '>', 0);

        $this->applyLocationRack($query, $request);
        if ($request->filled('category_id')) {
            $query->whereHas('item', fn ($q) => $q->where('category_id', $request->string('category_id')));
        }

        $rows = $query->orderBy('item_id')->get()->map(fn (ItemStock $stock) => [
            $stock->item?->sku,
            $stock->item?->name,
            $stock->item?->category?->code,
            $stock->location?->code,
            $stock->rack?->code,
            $stock->condition,
            (float) $stock->qty_on_hand,
            (float) $stock->qty_available,
            $stock->item?->uom?->code,
        ])->all();

        return [
            'key' => 'stock',
            'title' => 'Laporan Stok Terkini / Posisi',
            'columns' => ['SKU', 'Nama', 'Kategori', 'Lokasi', 'Rak', 'Kondisi', 'On Hand', 'Available', 'UOM'],
            'rows' => $rows,
            'meta' => $this->filterMeta($request, ['location_id', 'rack_id', 'category_id']),
        ];
    }

    /**
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>, meta: array<string, mixed>}
     */
    public function mutations(Request $request): array
    {
        [$from, $to] = $this->period($request);

        $query = StockLedger::query()
            ->with(['item:id,sku,name', 'location:id,code,name', 'rack:id,code', 'creator:id,name'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);

        $this->applyLocationRack($query, $request);
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->string('movement_type'));
        }

        $rows = $query->latest('created_at')->limit(2000)->get()->map(fn (StockLedger $ledger) => [
            $ledger->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            $ledger->item?->sku,
            $ledger->item?->name,
            $ledger->location?->code,
            $ledger->rack?->code,
            $ledger->movement_type,
            (float) $ledger->qty_delta,
            (float) $ledger->qty_before,
            (float) $ledger->qty_after,
            $ledger->creator?->name,
            $ledger->notes,
        ])->all();

        return [
            'key' => 'mutations',
            'title' => 'Laporan Mutasi Stok',
            'columns' => ['Waktu', 'SKU', 'Nama', 'Lokasi', 'Rak', 'Tipe', 'Delta', 'Sebelum', 'Sesudah', 'Oleh', 'Catatan'],
            'rows' => $rows,
            'meta' => array_merge(
                $this->filterMeta($request, ['location_id', 'rack_id', 'movement_type', 'date_from', 'date_to']),
                ['period' => $from->toDateString().' s/d '.$to->toDateString()],
            ),
        ];
    }

    /**
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>, meta: array<string, mixed>}
     */
    public function purchases(Request $request): array
    {
        [$from, $to] = $this->period($request);

        $pos = PurchaseOrder::query()
            ->with(['vendor:id,code,name'])
            ->withCount('lines')
            ->withCount('goodsReceipts')
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->string('vendor_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
            ->latest('order_date')
            ->limit(1000)
            ->get();

        $rows = $pos->map(fn (PurchaseOrder $po) => [
            $po->number,
            $po->order_date?->format('Y-m-d'),
            $po->vendor?->code.' — '.$po->vendor?->name,
            $po->status,
            (float) $po->total_amount,
            $po->lines_count,
            $po->goods_receipts_count,
        ])->all();

        return [
            'key' => 'purchases',
            'title' => 'Laporan PO & Receiving',
            'columns' => ['Nomor PO', 'Tanggal', 'Vendor', 'Status', 'Total', 'Baris', 'GR Count'],
            'rows' => $rows,
            'meta' => array_merge(
                $this->filterMeta($request, ['vendor_id', 'status', 'date_from', 'date_to']),
                ['period' => $from->toDateString().' s/d '.$to->toDateString()],
            ),
        ];
    }

    /**
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>, meta: array<string, mixed>}
     */
    public function opnames(Request $request): array
    {
        [$from, $to] = $this->period($request);

        $query = StockOpname::query()
            ->with(['location:id,code,name'])
            ->withCount([
                'lines',
                'lines as variance_lines_count' => fn ($q) => $q->whereNotNull('qty_variance')->where('qty_variance', '!=', 0),
            ])
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->string('location_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->whereBetween('opname_date', [$from->toDateString(), $to->toDateString()])
            ->latest('opname_date');

        $rows = $query->limit(1000)->get()->map(fn (StockOpname $opn) => [
            $opn->number,
            $opn->opname_date?->format('Y-m-d'),
            $opn->location?->code.' — '.$opn->location?->name,
            $opn->status,
            $opn->lines_count,
            $opn->variance_lines_count,
            $opn->posted_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ])->all();

        return [
            'key' => 'opnames',
            'title' => 'Laporan Opname & Selisih',
            'columns' => ['Nomor', 'Tanggal', 'Lokasi', 'Status', 'Baris', 'Selisih', 'Posted At'],
            'rows' => $rows,
            'meta' => array_merge(
                $this->filterMeta($request, ['location_id', 'status', 'date_from', 'date_to']),
                ['period' => $from->toDateString().' s/d '.$to->toDateString()],
            ),
        ];
    }

    /**
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>, meta: array<string, mixed>}
     */
    public function borrows(Request $request): array
    {
        $query = BorrowRequest::query()
            ->with(['borrower:id,name', 'client:id,code,name'])
            ->withCount('lines')
            ->when($request->filled('borrower_user_id'), fn ($q) => $q->where('borrower_user_id', $request->string('borrower_user_id')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->string('client_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('overdue'), function ($q) {
                $q->whereIn('status', ['checked_out', 'partially_returned'])
                    ->whereDate('due_date', '<', now()->toDateString());
            })
            ->latest();

        $rows = $query->limit(1000)->get()->map(fn (BorrowRequest $borrow) => [
            $borrow->number,
            $borrow->borrower?->name,
            ($borrow->client?->code ?? '').' — '.($borrow->client?->name ?? ''),
            $borrow->borrow_date?->format('Y-m-d'),
            $borrow->due_date?->format('Y-m-d'),
            $borrow->status,
            $borrow->isOverdue() ? 'Ya' : 'Tidak',
            $borrow->lines_count,
        ])->all();

        return [
            'key' => 'borrows',
            'title' => 'Laporan Peminjaman & Overdue',
            'columns' => ['Nomor', 'Peminjam', 'Client', 'Pinjam', 'Jatuh Tempo', 'Status', 'Overdue', 'Baris'],
            'rows' => $rows,
            'meta' => $this->filterMeta($request, ['borrower_user_id', 'client_id', 'status', 'overdue']),
        ];
    }

    /**
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>, meta: array<string, mixed>}
     */
    public function assets(Request $request): array
    {
        $query = AssetUnit::query()
            ->with([
                'item:id,sku,name',
                'location:id,code,name',
                'rack:id,code',
                'holder:id,name',
                'client:id,code,name',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->string('location_id')))
            ->when($request->filled('borrower_user_id'), fn ($q) => $q->where('current_holder_user_id', $request->string('borrower_user_id')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('current_client_id', $request->string('client_id')));

        $rows = $query->orderBy('asset_tag')->limit(2000)->get()->map(fn (AssetUnit $unit) => [
            $unit->asset_tag,
            $unit->serial_number,
            $unit->item?->sku,
            $unit->item?->name,
            $unit->status,
            $unit->condition,
            $unit->location?->code,
            $unit->rack?->code,
            $unit->holder?->name,
            $unit->client ? $unit->client->code.' — '.$unit->client->name : '',
        ])->all();

        return [
            'key' => 'assets',
            'title' => 'Laporan Status Asset',
            'columns' => ['Tag', 'Serial', 'SKU', 'Nama', 'Status', 'Kondisi', 'Lokasi', 'Rak', 'Holder', 'Client'],
            'rows' => $rows,
            'meta' => $this->filterMeta($request, ['status', 'location_id', 'borrower_user_id', 'client_id']),
        ];
    }

    /**
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>, meta: array<string, mixed>}
     */
    public function vendors(Request $request): array
    {
        $rows = Vendor::query()
            ->withCount('purchaseOrders')
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('code')
            ->get()
            ->map(fn (Vendor $vendor) => [
                $vendor->code,
                $vendor->name,
                $vendor->is_active ? 'Aktif' : 'Nonaktif',
                $vendor->purchase_orders_count,
                $vendor->phone,
                $vendor->email,
            ])->all();

        return [
            'key' => 'vendors',
            'title' => 'Laporan Vendor',
            'columns' => ['Kode', 'Nama', 'Status', 'Jumlah PO', 'Telepon', 'Email'],
            'rows' => $rows,
            'meta' => [],
        ];
    }

    /**
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>, meta: array<string, mixed>}
     */
    public function clients(Request $request): array
    {
        $rows = Client::query()
            ->withCount([
                'borrowRequests',
                'borrowRequests as open_borrows_count' => fn ($q) => $q->whereIn('status', ['checked_out', 'partially_returned']),
            ])
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('code')
            ->get()
            ->map(fn (Client $client) => [
                $client->code,
                $client->name,
                $client->type ?? '',
                $client->is_active ? 'Aktif' : 'Nonaktif',
                $client->borrow_requests_count,
                $client->open_borrows_count,
            ])->all();

        return [
            'key' => 'clients',
            'title' => 'Laporan Client (aktifitas pinjam)',
            'columns' => ['Kode', 'Nama', 'Tipe', 'Status', 'Total Pinjam', 'Pinjam Aktif'],
            'rows' => $rows,
            'meta' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'locations' => Location::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'racks' => Rack::query()->where('is_active', true)->orderBy('code')->get(['id', 'location_id', 'code', 'label']),
            'categories' => Category::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'vendors' => Vendor::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'clients' => Client::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'borrowers' => User::query()->orderBy('name')->get(['id', 'name']),
            'assetStatuses' => AssetUnit::STATUSES,
            'poStatuses' => PurchaseOrder::STATUSES,
            'opnameStatuses' => StockOpname::STATUSES,
            'borrowStatuses' => BorrowRequest::STATUSES,
        ];
    }

    /**
     * @return list<array{key: string, title: string, description: string}>
     */
    public function catalog(): array
    {
        return [
            ['key' => 'stock', 'title' => 'Stok Terkini / Posisi', 'description' => 'Item → lokasi → rak → qty'],
            ['key' => 'mutations', 'title' => 'Mutasi Stok', 'description' => 'Ledger per periode'],
            ['key' => 'purchases', 'title' => 'PO & Receiving', 'description' => 'Purchase order dan jumlah GR'],
            ['key' => 'opnames', 'title' => 'Opname & Selisih', 'description' => 'Sesi opname dan baris berselisih'],
            ['key' => 'borrows', 'title' => 'Peminjaman & Overdue', 'description' => 'Filter peminjam / client'],
            ['key' => 'assets', 'title' => 'Status Asset', 'description' => 'Status, lokasi, holder, client'],
            ['key' => 'vendors', 'title' => 'Vendor', 'description' => 'Ringkasan vendor + jumlah PO'],
            ['key' => 'clients', 'title' => 'Client', 'description' => 'Aktifitas pinjam per client'],
        ];
    }

    public function resolve(string $key, Request $request): array
    {
        return match ($key) {
            'stock' => $this->stock($request),
            'mutations' => $this->mutations($request),
            'purchases' => $this->purchases($request),
            'opnames' => $this->opnames($request),
            'borrows' => $this->borrows($request),
            'assets' => $this->assets($request),
            'vendors' => $this->vendors($request),
            'clients' => $this->clients($request),
            default => abort(404),
        };
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyLocationRack($query, Request $request): void
    {
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->string('location_id'));
        }
        if ($request->filled('rack_id')) {
            $query->where('rack_id', $request->string('rack_id'));
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        $from = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'))
            : now()->subDays(30);
        $to = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'))
            : now();

        return [$from, $to];
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function filterMeta(Request $request, array $keys): array
    {
        $meta = [];
        foreach ($keys as $key) {
            $meta[$key] = $request->input($key);
        }

        return $meta;
    }
}
