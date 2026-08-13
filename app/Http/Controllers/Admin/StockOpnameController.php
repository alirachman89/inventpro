<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStockOpnameRequest;
use App\Http\Requests\Admin\UpdateStockOpnameCountsRequest;
use App\Models\Location;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StockOpnameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function __construct(
        private readonly StockOpnameService $opnameService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $opnames = StockOpname::query()
            ->with(['location:id,code,name', 'pic:id,name', 'creator:id,name'])
            ->withCount([
                'lines',
                'lines as variance_lines_count' => fn ($q) => $q->whereNotNull('qty_variance')->where('qty_variance', '!=', 0),
            ])
            ->when($search !== '', fn ($q) => $q->where('number', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (StockOpname $opname) => [
                'id' => $opname->id,
                'number' => $opname->number,
                'status' => $opname->status,
                'opname_date' => $opname->opname_date?->format('Y-m-d'),
                'location' => $opname->location?->only(['code', 'name']),
                'pic' => $opname->pic?->name,
                'lines_count' => $opname->lines_count,
                'variance_lines_count' => $opname->variance_lines_count,
            ]);

        return Inertia::render('Admin/StockOpnames/Index', [
            'opnames' => $opnames,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => StockOpname::STATUSES,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/StockOpnames/Form', [
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreStockOpnameRequest $request): RedirectResponse
    {
        $opname = $this->opnameService->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.stock-opnames.show', $opname)
            ->with('success', "Sesi {$opname->number} dibuat. Isi qty fisik.");
    }

    public function show(StockOpname $stockOpname): Response
    {
        $stockOpname->load([
            'location:id,code,name',
            'pic:id,name',
            'creator:id,name',
            'lines.item:id,sku,name',
            'lines.rack:id,code,label',
        ]);

        $summary = [
            'lines' => $stockOpname->lines->count(),
            'counted' => $stockOpname->lines->whereNotNull('qty_counted')->count(),
            'variance' => $stockOpname->lines->filter(fn ($l) => (float) ($l->qty_variance ?? 0) != 0.0)->count(),
            'variance_qty' => (float) $stockOpname->lines->sum('qty_variance'),
        ];

        return Inertia::render('Admin/StockOpnames/Show', [
            'opname' => [
                'id' => $stockOpname->id,
                'number' => $stockOpname->number,
                'status' => $stockOpname->status,
                'opname_date' => $stockOpname->opname_date?->format('Y-m-d'),
                'notes' => $stockOpname->notes,
                'location' => $stockOpname->location?->only(['code', 'name']),
                'pic' => $stockOpname->pic?->name,
                'creator' => $stockOpname->creator?->name,
                'submitted_at' => $stockOpname->submitted_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'approved_at' => $stockOpname->approved_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'posted_at' => $stockOpname->posted_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'can_edit' => $stockOpname->canEdit(),
                'can_submit' => $stockOpname->canSubmit(),
                'can_post' => $stockOpname->canPost(),
                'lines' => $stockOpname->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'item' => $line->item?->only(['sku', 'name']),
                    'rack' => $line->rack?->only(['code', 'label']),
                    'condition' => $line->condition,
                    'qty_system' => (float) $line->qty_system,
                    'qty_counted' => $line->qty_counted !== null ? (float) $line->qty_counted : null,
                    'qty_variance' => $line->qty_variance !== null ? (float) $line->qty_variance : null,
                    'notes' => $line->notes,
                ]),
            ],
            'summary' => $summary,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function updateCounts(UpdateStockOpnameCountsRequest $request, StockOpname $stockOpname): RedirectResponse
    {
        try {
            $this->opnameService->updateCounts($stockOpname, $request->validated('lines'), $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return back()->with('success', 'Qty fisik disimpan.');
    }

    public function refresh(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        abort_unless($request->user()?->can('stock_opnames.update'), 403);

        try {
            $this->opnameService->generateLines($stockOpname);
        } catch (ValidationException $e) {
            throw $e;
        }

        return back()->with('success', 'Daftar item & qty sistem diperbarui.');
    }

    public function submit(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        abort_unless($request->user()?->can('stock_opnames.submit'), 403);

        try {
            $this->opnameService->submit($stockOpname, $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        $stockOpname->refresh();

        $message = $stockOpname->status === 'posted'
            ? 'Tidak ada selisih — opname langsung selesai.'
            : 'Opname diajukan untuk approval selisih.';

        return redirect()
            ->route('admin.stock-opnames.show', $stockOpname)
            ->with('success', $message);
    }

    public function post(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        abort_unless($request->user()?->can('stock_opnames.post'), 403);

        try {
            $this->opnameService->post($stockOpname, $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('admin.stock-opnames.show', $stockOpname)
            ->with('success', 'Opname diposting. Stok disesuaikan.');
    }

    public function destroy(StockOpname $stockOpname): RedirectResponse
    {
        if (! in_array($stockOpname->status, ['draft', 'rejected', 'cancelled'], true)) {
            return back()->with('error', 'Hanya sesi draft/rejected yang dapat dihapus.');
        }

        $number = $stockOpname->number;
        $stockOpname->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'stock_opnames',
            description: "Sesi opname {$number} dihapus",
        );

        return redirect()
            ->route('admin.stock-opnames.index')
            ->with('success', 'Sesi opname dihapus.');
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Menunggu approval',
            'approved' => 'Disetujui — siap posting',
            'rejected' => 'Ditolak',
            'posted' => 'Posted',
            'cancelled' => 'Dibatalkan',
        ];
    }
}
