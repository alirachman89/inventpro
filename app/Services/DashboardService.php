<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\AssetUnit;
use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\StockLedger;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $locationId = $request->string('location_id')->toString() ?: null;
        $days = max(7, min(90, (int) $request->input('days', 30)));
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        return [
            'filters' => [
                'location_id' => $locationId,
                'days' => $days,
            ],
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'kpis' => $this->kpis($locationId),
            'asset_status' => $this->assetStatus($locationId),
            'mutation_chart' => $this->mutationChart($from, $to, $locationId),
            'top_items' => $this->topMovingItems($from, $to, $locationId),
            'alerts' => $this->alerts($locationId),
            'overdue_borrows' => $this->overdueBorrows(),
            'work_queue' => $this->workQueue($request->user()),
            'period_label' => $from->toDateString().' s/d '.$to->toDateString(),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function kpis(?string $locationId): array
    {
        $stockQuery = ItemStock::query();
        if ($locationId) {
            $stockQuery->where('location_id', $locationId);
        }

        $totalQty = (float) (clone $stockQuery)->sum('qty_on_hand');

        $lowStock = Item::query()
            ->where('is_active', true)
            ->where('item_type', 'consumable')
            ->where('min_stock', '>', 0)
            ->withSum([
                'stocks as stock_sum' => function ($q) use ($locationId) {
                    if ($locationId) {
                        $q->where('location_id', $locationId);
                    }
                },
            ], 'qty_on_hand')
            ->get()
            ->filter(fn (Item $item) => (float) ($item->stock_sum ?? 0) <= (float) $item->min_stock)
            ->count();

        return [
            'total_items' => Item::query()->where('is_active', true)->count(),
            'total_qty' => $totalQty,
            'low_stock' => $lowStock,
            'open_po' => PurchaseOrder::query()
                ->whereIn('status', ['submitted', 'ordered', 'partially_received'])
                ->count(),
            'open_borrows' => BorrowRequest::query()
                ->whereIn('status', ['checked_out', 'partially_returned'])
                ->count(),
            'overdue_borrows' => BorrowRequest::query()
                ->whereIn('status', ['checked_out', 'partially_returned'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];
    }

    /**
     * @return list<array{status: string, count: int}>
     */
    private function assetStatus(?string $locationId): array
    {
        $query = AssetUnit::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->groupBy('status');

        $counts = $query->pluck('aggregate', 'status');

        $focus = ['available', 'borrowed', 'maintenance', 'damaged', 'in_transit', 'quarantine'];

        return collect($focus)->map(fn (string $status) => [
            'status' => $status,
            'count' => (int) ($counts[$status] ?? 0),
        ])->all();
    }

    /**
     * @return list<array{date: string, inbound: float, outbound: float}>
     */
    private function mutationChart(Carbon $from, Carbon $to, ?string $locationId): array
    {
        $ledgers = StockLedger::query()
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('SUM(CASE WHEN qty_delta > 0 THEN qty_delta ELSE 0 END) as inbound')
            ->selectRaw('SUM(CASE WHEN qty_delta < 0 THEN ABS(qty_delta) ELSE 0 END) as outbound')
            ->whereBetween('created_at', [$from, $to])
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $days = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $row = $ledgers->get($key);
            $days[] = [
                'date' => $key,
                'inbound' => (float) ($row->inbound ?? 0),
                'outbound' => (float) ($row->outbound ?? 0),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @return list<array{sku: string, name: string, movement: float}>
     */
    private function topMovingItems(Carbon $from, Carbon $to, ?string $locationId): array
    {
        return StockLedger::query()
            ->select('item_id')
            ->selectRaw('SUM(ABS(qty_delta)) as movement')
            ->with('item:id,sku,name')
            ->whereBetween('created_at', [$from, $to])
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->groupBy('item_id')
            ->orderByDesc('movement')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'sku' => $row->item?->sku,
                'name' => $row->item?->name,
                'movement' => (float) $row->movement,
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, message: string, href: ?string}>
     */
    private function alerts(?string $locationId): array
    {
        $alerts = [];

        $lowItems = Item::query()
            ->where('is_active', true)
            ->where('item_type', 'consumable')
            ->where('min_stock', '>', 0)
            ->withSum([
                'stocks as stock_sum' => function ($q) use ($locationId) {
                    if ($locationId) {
                        $q->where('location_id', $locationId);
                    }
                },
            ], 'qty_on_hand')
            ->orderBy('sku')
            ->get()
            ->filter(fn (Item $item) => (float) ($item->stock_sum ?? 0) <= (float) $item->min_stock)
            ->take(5);

        foreach ($lowItems as $item) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low stock',
                'message' => "{$item->sku} — {$item->name} (on hand ".((float) ($item->stock_sum ?? 0))." / min {$item->min_stock})",
                'href' => route('admin.items.show', $item->id),
            ];
        }

        $pendingPo = PurchaseOrder::query()
            ->where('status', 'submitted')
            ->count();
        if ($pendingPo > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'PO menunggu approval',
                'message' => "{$pendingPo} PO berstatus submitted.",
                'href' => route('admin.purchase-orders.index', ['status' => 'submitted']),
            ];
        }

        $overdue = BorrowRequest::query()
            ->whereIn('status', ['checked_out', 'partially_returned'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
        if ($overdue > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Peminjaman overdue',
                'message' => "{$overdue} pinjaman melewati jatuh tempo.",
                'href' => route('admin.borrows.index', ['overdue' => 1]),
            ];
        }

        $pendingOpname = StockOpname::query()
            ->where('status', 'submitted')
            ->count();
        if ($pendingOpname > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Opname menunggu approval',
                'message' => "{$pendingOpname} sesi opname menunggu persetujuan selisih.",
                'href' => route('admin.stock-opnames.index', ['status' => 'submitted']),
            ];
        }

        return $alerts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function overdueBorrows(): array
    {
        return BorrowRequest::query()
            ->with(['borrower:id,name', 'client:id,code,name'])
            ->whereIn('status', ['checked_out', 'partially_returned'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->limit(8)
            ->get()
            ->map(fn (BorrowRequest $borrow) => [
                'id' => $borrow->id,
                'number' => $borrow->number,
                'borrower' => $borrow->borrower?->name,
                'client' => $borrow->client ? $borrow->client->code.' — '.$borrow->client->name : null,
                'due_date' => $borrow->due_date?->format('Y-m-d'),
                'days_overdue' => $borrow->due_date
                    ? $borrow->due_date->diffInDays(now()->startOfDay())
                    : 0,
            ])
            ->all();
    }

    /**
     * Actionable work queue for warehouse/ops.
     *
     * @return list<array{priority: string, title: string, meta: string, href: ?string, action: string}>
     */
    private function workQueue(?User $user): array
    {
        $queue = [];

        if (! $user) {
            return $queue;
        }

        if ($user->can('goods_receipts.create') || $user->can('purchases.view')) {
            PurchaseOrder::query()
                ->with('vendor:id,code,name')
                ->whereIn('status', ['ordered', 'partially_received'])
                ->latest('order_date')
                ->limit(5)
                ->get()
                ->each(function (PurchaseOrder $po) use (&$queue, $user) {
                    $queue[] = [
                        'priority' => 'high',
                        'title' => "GR siap: {$po->number}",
                        'meta' => ($po->vendor?->code ?? '').' · '.$po->status,
                        'href' => $user->can('purchases.view')
                            ? route('admin.purchase-orders.show', $po->id)
                            : null,
                        'action' => 'Buat Goods Receipt',
                    ];
                });
        }

        if ($user->can('borrows.checkout') || $user->can('borrows.view')) {
            BorrowRequest::query()
                ->with(['borrower:id,name', 'client:id,code,name'])
                ->where('status', 'approved')
                ->latest('approved_at')
                ->limit(5)
                ->get()
                ->each(function (BorrowRequest $borrow) use (&$queue, $user) {
                    $queue[] = [
                        'priority' => 'high',
                        'title' => "Checkout pinjam: {$borrow->number}",
                        'meta' => ($borrow->borrower?->name ?? '').' → '.($borrow->client?->code ?? ''),
                        'href' => $user->can('borrows.view')
                            ? route('admin.borrows.show', $borrow->id)
                            : null,
                        'action' => 'Checkout',
                    ];
                });
        }

        if ($user->can('borrows.return') || $user->can('borrows.view')) {
            BorrowRequest::query()
                ->with(['borrower:id,name', 'client:id,code,name'])
                ->whereIn('status', ['checked_out', 'partially_returned'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->orderBy('due_date')
                ->limit(5)
                ->get()
                ->each(function (BorrowRequest $borrow) use (&$queue, $user) {
                    $queue[] = [
                        'priority' => 'critical',
                        'title' => "Return overdue: {$borrow->number}",
                        'meta' => ($borrow->borrower?->name ?? '').' / '.($borrow->client?->code ?? '').' · JT '.$borrow->due_date?->format('Y-m-d'),
                        'href' => $user->can('borrows.view')
                            ? route('admin.borrows.show', $borrow->id)
                            : null,
                        'action' => 'Return',
                    ];
                });
        }

        if ($user->can('stock_opnames.update') || $user->can('stock_opnames.view')) {
            StockOpname::query()
                ->with('location:id,code,name')
                ->whereIn('status', ['draft', 'rejected'])
                ->latest()
                ->limit(4)
                ->get()
                ->each(function (StockOpname $opn) use (&$queue, $user) {
                    $queue[] = [
                        'priority' => 'medium',
                        'title' => "Lanjutkan opname: {$opn->number}",
                        'meta' => ($opn->location?->code ?? '').' · '.$opn->status,
                        'href' => $user->can('stock_opnames.view')
                            ? route('admin.stock-opnames.show', $opn->id)
                            : null,
                        'action' => 'Input qty / ajukan',
                    ];
                });
        }

        if ($user->can('stock_opnames.post') || $user->can('stock_opnames.view')) {
            StockOpname::query()
                ->with('location:id,code,name')
                ->where('status', 'approved')
                ->latest('approved_at')
                ->limit(4)
                ->get()
                ->each(function (StockOpname $opn) use (&$queue, $user) {
                    $queue[] = [
                        'priority' => 'high',
                        'title' => "Posting opname: {$opn->number}",
                        'meta' => ($opn->location?->code ?? '').' · siap posting',
                        'href' => $user->can('stock_opnames.view')
                            ? route('admin.stock-opnames.show', $opn->id)
                            : null,
                        'action' => 'Posting ke stok',
                    ];
                });
        }

        if ($user->can('approvals.act')) {
            $pendingApprovals = ApprovalRequest::query()
                ->where('status', 'pending')
                ->count();
            if ($pendingApprovals > 0) {
                $queue[] = [
                    'priority' => 'high',
                    'title' => "Persetujuan menunggu ({$pendingApprovals})",
                    'meta' => 'Cek antrean My Approvals',
                    'href' => route('approvals.index'),
                    'action' => 'Buka Persetujuan',
                ];
            }
        }

        $order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

        usort($queue, fn ($a, $b) => ($order[$a['priority']] ?? 9) <=> ($order[$b['priority']] ?? 9));

        return array_slice($queue, 0, 12);
    }
}
