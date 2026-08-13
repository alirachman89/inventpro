<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PurchaseOrderExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePurchaseOrderRequest;
use App\Http\Requests\Admin\UpdatePurchaseOrderRequest;
use App\Models\ApprovalRequest;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\ApprovalEngine;
use App\Services\AuditLogger;
use App\Services\DocumentNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly DocumentNumberService $documentNumbers,
        private readonly ApprovalEngine $approvalEngine,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $orders = PurchaseOrder::query()
            ->with(['vendor:id,code,name', 'creator:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PurchaseOrder $po) => [
                'id' => $po->id,
                'number' => $po->number,
                'vendor' => $po->vendor?->only(['code', 'name']),
                'status' => $po->status,
                'order_date' => $po->order_date?->format('Y-m-d'),
                'total_amount' => (float) $po->total_amount,
                'creator' => $po->creator?->name,
            ]);

        return Inertia::render('Admin/PurchaseOrders/Index', [
            'orders' => $orders,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => PurchaseOrder::STATUSES,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/PurchaseOrders/Form', [
            'order' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $po = DB::transaction(function () use ($data, $request) {
            $po = PurchaseOrder::query()->create([
                'number' => $this->documentNumbers->next('prefix_po', 'PO'),
                'vendor_id' => $data['vendor_id'],
                'status' => 'draft',
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
                'total_amount' => 0,
            ]);

            $this->syncLines($po, $data['lines']);

            return $po->fresh('lines');
        });

        $this->auditLogger->log(
            action: 'created',
            module: 'purchases',
            description: "PO {$po->number} dibuat",
            auditable: $po,
            newValues: ['vendor_id' => $po->vendor_id, 'total_amount' => $po->total_amount],
        );

        if ($request->boolean('submit_now')) {
            $this->approvalEngine->submit(
                PurchaseOrder::DOCUMENT_TYPE,
                $po,
                $request->user(),
                $po->number,
            );

            return redirect()
                ->route('admin.purchase-orders.show', $po)
                ->with('success', 'PO dibuat dan diajukan untuk approval.');
        }

        return redirect()
            ->route('admin.purchase-orders.show', $po)
            ->with('success', 'PO berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load([
            'vendor:id,code,name',
            'creator:id,name',
            'lines.item:id,sku,name,item_type,is_serialized,uom_id',
            'lines.item.uom:id,code,name',
            'goodsReceipts.location:id,code,name',
            'goodsReceipts.receiver:id,name',
        ]);

        return Inertia::render('Admin/PurchaseOrders/Show', [
            'order' => [
                'id' => $purchaseOrder->id,
                'number' => $purchaseOrder->number,
                'status' => $purchaseOrder->status,
                'order_date' => $purchaseOrder->order_date?->format('Y-m-d'),
                'expected_date' => $purchaseOrder->expected_date?->format('Y-m-d'),
                'notes' => $purchaseOrder->notes,
                'total_amount' => (float) $purchaseOrder->total_amount,
                'vendor' => $purchaseOrder->vendor?->only(['id', 'code', 'name']),
                'creator' => $purchaseOrder->creator?->name,
                'can_edit' => $purchaseOrder->canEdit(),
                'can_submit' => $purchaseOrder->canSubmit(),
                'can_receive' => $purchaseOrder->canReceive(),
                'lines' => $purchaseOrder->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'item' => [
                        'id' => $line->item?->id,
                        'sku' => $line->item?->sku,
                        'name' => $line->item?->name,
                        'uom' => $line->item?->uom ? "{$line->item->uom->code}" : null,
                        'item_type' => $line->item?->item_type,
                    ],
                    'qty_ordered' => (float) $line->qty_ordered,
                    'qty_received' => (float) $line->qty_received,
                    'qty_outstanding' => $line->qtyOutstanding(),
                    'unit_price' => (float) $line->unit_price,
                    'line_total' => (float) $line->line_total,
                ]),
                'receipts' => $purchaseOrder->goodsReceipts->map(fn ($gr) => [
                    'id' => $gr->id,
                    'number' => $gr->number,
                    'location' => $gr->location?->only(['code', 'name']),
                    'received_date' => $gr->received_date?->format('Y-m-d'),
                    'receiver' => $gr->receiver?->name,
                ]),
            ],
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response|RedirectResponse
    {
        if (! $purchaseOrder->canEdit()) {
            return redirect()
                ->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('error', 'PO tidak dapat diedit pada status ini.');
        }

        $purchaseOrder->load('lines');

        return Inertia::render('Admin/PurchaseOrders/Form', [
            'order' => [
                'id' => $purchaseOrder->id,
                'number' => $purchaseOrder->number,
                'vendor_id' => $purchaseOrder->vendor_id,
                'order_date' => $purchaseOrder->order_date?->format('Y-m-d'),
                'expected_date' => $purchaseOrder->expected_date?->format('Y-m-d'),
                'notes' => $purchaseOrder->notes,
                'lines' => $purchaseOrder->lines->map(fn ($line) => [
                    'item_id' => $line->item_id,
                    'qty_ordered' => (float) $line->qty_ordered,
                    'unit_price' => (float) $line->unit_price,
                    'notes' => $line->notes,
                ])->values(),
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $purchaseOrder->canEdit()) {
            return back()->with('error', 'PO tidak dapat diedit pada status ini.');
        }

        $data = $request->validated();

        DB::transaction(function () use ($purchaseOrder, $data) {
            $purchaseOrder->update([
                'vendor_id' => $data['vendor_id'],
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
            ]);

            $purchaseOrder->lines()->delete();
            $this->syncLines($purchaseOrder, $data['lines']);
        });

        $this->auditLogger->log(
            action: 'updated',
            module: 'purchases',
            description: "PO {$purchaseOrder->number} diperbarui",
            auditable: $purchaseOrder,
        );

        return redirect()
            ->route('admin.purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO berhasil diperbarui.');
    }

    public function submit(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()?->can('purchases.submit'), 403);

        if (! $purchaseOrder->canSubmit()) {
            return back()->with('error', 'PO tidak dapat diajukan.');
        }

        $this->approvalEngine->submit(
            PurchaseOrder::DOCUMENT_TYPE,
            $purchaseOrder,
            $request->user(),
            $purchaseOrder->number,
        );

        return redirect()
            ->route('admin.purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO diajukan untuk approval.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! in_array($purchaseOrder->status, ['draft', 'rejected', 'cancelled'], true)) {
            return back()->with('error', 'Hanya PO draft/rejected/cancelled yang dapat dihapus.');
        }

        $number = $purchaseOrder->number;
        $purchaseOrder->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'purchases',
            description: "PO {$number} dihapus",
        );

        return redirect()
            ->route('admin.purchase-orders.index')
            ->with('success', 'PO berhasil dihapus.');
    }

    public function exportPdf(PurchaseOrder $purchaseOrder): HttpResponse
    {
        $purchaseOrder->load([
            'vendor',
            'creator:id,name',
            'lines.item.uom:id,code,name',
        ]);

        $approval = ApprovalRequest::query()
            ->with(['actions' => fn ($q) => $q->where('action', 'approve')->with('actor:id,name')->latest('created_at')])
            ->where('document_type', PurchaseOrder::DOCUMENT_TYPE)
            ->where('document_id', $purchaseOrder->id)
            ->where('status', 'approved')
            ->latest('completed_at')
            ->first();

        $lastApprove = $approval?->actions->first();
        $preparedAt = $purchaseOrder->submitted_at ?? $purchaseOrder->order_date;
        $approvedAt = $lastApprove?->created_at ?? $purchaseOrder->approved_at;

        $pdf = Pdf::loadView('exports.purchase-order-pdf', [
            'order' => $purchaseOrder,
            'statusLabel' => $this->statusLabels()[$purchaseOrder->status] ?? $purchaseOrder->status,
            'company' => [
                'name' => Setting::getValue('company_name', 'InventPro'),
                'address' => Setting::getValue('company_address', ''),
                'phone' => Setting::getValue('company_phone', ''),
            ],
            'signatures' => [
                'prepared' => [
                    'name' => $purchaseOrder->creator?->name,
                    'date' => $preparedAt?->timezone(config('app.timezone'))->format('d/m/Y'),
                ],
                'approved' => [
                    'name' => $lastApprove?->actor?->name,
                    'date' => $approvedAt?->timezone(config('app.timezone'))->format('d/m/Y'),
                ],
                'vendor' => [
                    'name' => null,
                    'date' => null,
                ],
            ],
        ])->setPaper('a4', 'portrait');

        $this->auditLogger->log(
            action: 'exported',
            module: 'purchases',
            description: "PO {$purchaseOrder->number} diekspor PDF",
            auditable: $purchaseOrder,
            newValues: ['format' => 'pdf'],
        );

        $filename = str_replace(['/', '\\'], '-', $purchaseOrder->number).'.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(PurchaseOrder $purchaseOrder): BinaryFileResponse
    {
        $purchaseOrder->load([
            'vendor',
            'creator:id,name',
            'lines.item.uom:id,code,name',
        ]);

        $this->auditLogger->log(
            action: 'exported',
            module: 'purchases',
            description: "PO {$purchaseOrder->number} diekspor Excel",
            auditable: $purchaseOrder,
            newValues: ['format' => 'xlsx'],
        );

        $filename = str_replace(['/', '\\'], '-', $purchaseOrder->number).'.xlsx';

        return Excel::download(new PurchaseOrderExport($purchaseOrder), $filename);
    }

    /**
     * @param  list<array{item_id: string, qty_ordered: mixed, unit_price: mixed, notes?: ?string}>  $lines
     */
    private function syncLines(PurchaseOrder $po, array $lines): void
    {
        $total = 0;
        $lineNo = 1;

        foreach ($lines as $line) {
            $qty = (float) $line['qty_ordered'];
            $price = (float) $line['unit_price'];
            $lineTotal = round($qty * $price, 2);
            $total += $lineTotal;

            $po->lines()->create([
                'item_id' => $line['item_id'],
                'line_no' => $lineNo++,
                'qty_ordered' => $qty,
                'qty_received' => 0,
                'unit_price' => $price,
                'line_total' => $lineTotal,
                'notes' => $line['notes'] ?? null,
            ]);
        }

        $po->update(['total_amount' => $total]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'vendors' => Vendor::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'items' => Item::query()
                ->with('uom:id,code')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'sku', 'name', 'uom_id', 'item_type'])
                ->map(fn (Item $item) => [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'uom' => $item->uom?->code,
                    'item_type' => $item->item_type,
                ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Menunggu approval',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'ordered' => 'Siap diterima',
            'partially_received' => 'Diterima sebagian',
            'received' => 'Diterima penuh',
            'cancelled' => 'Dibatalkan',
        ];
    }
}
