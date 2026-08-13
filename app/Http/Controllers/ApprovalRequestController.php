<?php

namespace App\Http\Controllers;

use App\Http\Requests\Approval\DecideApprovalRequest;
use App\Models\ApprovalDemo;
use App\Models\ApprovalRequest;
use App\Models\BorrowRequest;
use App\Models\PurchaseOrder;
use App\Models\StockOpname;
use App\Services\ApprovalEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalRequestController extends Controller
{
    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $pending = ApprovalRequest::query()
            ->with(['workflow.steps', 'submitter:id,name,email'])
            ->where('status', 'pending')
            ->latest('submitted_at')
            ->get()
            ->filter(fn (ApprovalRequest $item) => $this->engine->userCanActOnRequest($user, $item))
            ->values()
            ->map(fn (ApprovalRequest $item) => $this->transform($item, $user));

        $mine = ApprovalRequest::query()
            ->with(['workflow.steps', 'submitter:id,name,email'])
            ->where('submitted_by', $user->id)
            ->latest('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (ApprovalRequest $item) => $this->transform($item, $user));

        return Inertia::render('Approvals/Index', [
            'pending' => $pending,
            'mine' => $mine,
        ]);
    }

    public function show(Request $request, ApprovalRequest $approval): Response
    {
        $user = $request->user();
        $approval->load(['workflow.steps', 'actions.actor', 'submitter']);

        abort_unless(
            $approval->submitted_by === $user->id
                || $this->engine->userCanActOnRequest($user, $approval)
                || $user->can('approvals.manage')
                || $user->can('approvals.act'),
            403,
        );

        return Inertia::render('Approvals/Show', [
            'approval' => $this->transform($approval, $request->user(), detailed: true),
            'canAct' => $this->engine->userCanActOnRequest($request->user(), $approval),
        ]);
    }

    public function approve(DecideApprovalRequest $request, ApprovalRequest $approval): RedirectResponse
    {
        $this->engine->approve(
            $approval,
            $request->user(),
            $request->validated('comment'),
        );

        return redirect()
            ->route('approvals.show', $approval->id)
            ->with('success', 'Dokumen berhasil disetujui.');
    }

    public function reject(DecideApprovalRequest $request, ApprovalRequest $approval): RedirectResponse
    {
        $this->engine->reject(
            $approval,
            $request->user(),
            $request->validated('comment'),
        );

        return redirect()
            ->route('approvals.show', $approval->id)
            ->with('success', 'Dokumen berhasil ditolak.');
    }

    private function transform(ApprovalRequest $item, $user, bool $detailed = false): array
    {
        $step = $item->currentStep();

        $payload = [
            'id' => $item->id,
            'document_type' => $item->document_type,
            'document_type_label' => $this->documentTypeLabel($item->document_type),
            'document_label' => $item->document_label,
            'status' => $item->status,
            'current_step_order' => $item->current_step_order,
            'current_step_name' => $step?->name,
            'submitted_at' => $item->submitted_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'completed_at' => $item->completed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'submitter' => [
                'name' => $item->submitter?->name,
                'email' => $item->submitter?->email,
            ],
            'can_act' => $this->engine->userCanActOnRequest($user, $item),
        ];

        if ($detailed) {
            $payload['steps'] = $item->workflow?->steps->map(fn ($s) => [
                'step_order' => $s->step_order,
                'name' => $s->name,
                'approver_role' => $s->approver_role,
                'mode' => $s->mode,
            ])->values();

            $payload['actions'] = $item->actions->map(fn ($action) => [
                'action' => $action->action,
                'comment' => $action->comment,
                'step_order' => $action->step_order,
                'actor_name' => $action->actor?->name,
                'created_at' => $action->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ])->values();

            $payload['document'] = $this->documentContext($item, $user);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function documentContext(ApprovalRequest $item, $user): ?array
    {
        return match ($item->document_type) {
            StockOpname::DOCUMENT_TYPE => $this->stockOpnameContext($item, $user),
            PurchaseOrder::DOCUMENT_TYPE => $this->purchaseOrderContext($item, $user),
            BorrowRequest::DOCUMENT_TYPE => $this->borrowRequestContext($item, $user),
            ApprovalDemo::DOCUMENT_TYPE => $this->approvalDemoContext($item),
            default => [
                'kind' => 'generic',
                'title' => $item->document_label,
                'summary' => [],
                'lines' => [],
                'url' => null,
            ],
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function borrowRequestContext(ApprovalRequest $item, $user): ?array
    {
        $borrow = BorrowRequest::query()
            ->with([
                'borrower:id,name,email',
                'client:id,code,name',
                'lines.item:id,sku,name',
                'lines.assetUnit:id,asset_tag,serial_number',
            ])
            ->find($item->document_id);

        if (! $borrow) {
            return null;
        }

        $url = $user->can('borrows.view')
            ? route('admin.borrows.show', $borrow->id)
            : null;

        return [
            'kind' => 'borrow_request',
            'title' => $borrow->number,
            'purpose' => 'Persetujuan peminjaman barang (peminjam karyawan ≠ client lokasi pakai).',
            'url' => $url,
            'url_label' => 'Buka detail Peminjaman',
            'summary' => [
                ['label' => 'Peminjam (Karyawan)', 'value' => $borrow->borrower?->name ?? '—'],
                ['label' => 'Digunakan di Client', 'value' => trim(($borrow->client?->code ?? '').' — '.($borrow->client?->name ?? ''), ' —')],
                ['label' => 'Tanggal pinjam', 'value' => $borrow->borrow_date?->format('Y-m-d') ?? '—'],
                ['label' => 'Jatuh tempo', 'value' => $borrow->due_date?->format('Y-m-d') ?? '—'],
                ['label' => 'Tujuan', 'value' => $borrow->purpose ?: '—'],
            ],
            'lines_title' => 'Barang dipinjam',
            'line_columns' => ['Item', 'Unit / Qty', 'Catatan'],
            'lines' => $borrow->lines->map(fn ($line) => [
                ($line->item?->sku ?? '').' — '.($line->item?->name ?? ''),
                $line->assetUnit
                    ? $line->assetUnit->asset_tag.($line->assetUnit->serial_number ? ' / '.$line->assetUnit->serial_number : '')
                    : $this->formatQty((float) $line->qty),
                $line->notes ?: '—',
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function stockOpnameContext(ApprovalRequest $item, $user): ?array
    {
        $opname = StockOpname::query()
            ->with(['location:id,code,name', 'lines.item:id,sku,name', 'lines.rack:id,code,label'])
            ->find($item->document_id);

        if (! $opname) {
            return null;
        }

        $varianceLines = $opname->lines
            ->filter(fn ($line) => (float) ($line->qty_variance ?? 0) != 0.0)
            ->values();

        $url = $user->can('stock_opnames.view')
            ? route('admin.stock-opnames.show', $opname->id)
            : null;

        return [
            'kind' => 'stock_opname',
            'title' => $opname->number,
            'purpose' => 'Persetujuan penyesuaian stok berdasarkan selisih opname.',
            'url' => $url,
            'url_label' => 'Buka detail Stock Opname',
            'summary' => [
                ['label' => 'Lokasi', 'value' => trim(($opname->location?->code ?? '').' — '.($opname->location?->name ?? ''), ' —')],
                ['label' => 'Tanggal', 'value' => $opname->opname_date?->format('Y-m-d') ?? '—'],
                ['label' => 'Total baris', 'value' => (string) $opname->lines->count()],
                ['label' => 'Baris berselisih', 'value' => (string) $varianceLines->count()],
                ['label' => 'Total selisih qty', 'value' => $this->formatQty((float) $varianceLines->sum('qty_variance'))],
                ['label' => 'Catatan', 'value' => $opname->notes ?: '—'],
            ],
            'lines_title' => 'Baris berselisih (yang perlu di-approve)',
            'line_columns' => ['Item', 'Rak', 'Sistem', 'Fisik', 'Selisih'],
            'lines' => $varianceLines->map(fn ($line) => [
                $line->item?->sku.' — '.$line->item?->name,
                ($line->rack?->code ?? '—').' ('.$line->condition.')',
                $this->formatQty((float) $line->qty_system),
                $line->qty_counted !== null ? $this->formatQty((float) $line->qty_counted) : '—',
                $this->formatQty((float) $line->qty_variance),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function purchaseOrderContext(ApprovalRequest $item, $user): ?array
    {
        $po = PurchaseOrder::query()
            ->with(['vendor:id,code,name', 'lines.item:id,sku,name'])
            ->find($item->document_id);

        if (! $po) {
            return null;
        }

        $url = $user->can('purchases.view')
            ? route('admin.purchase-orders.show', $po->id)
            : null;

        return [
            'kind' => 'purchase_order',
            'title' => $po->number,
            'purpose' => 'Persetujuan Purchase Order sebelum menjadi ordered.',
            'url' => $url,
            'url_label' => 'Buka detail Purchase Order',
            'summary' => [
                ['label' => 'Vendor', 'value' => trim(($po->vendor?->code ?? '').' — '.($po->vendor?->name ?? ''), ' —')],
                ['label' => 'Tanggal PO', 'value' => $po->order_date?->format('Y-m-d') ?? '—'],
                ['label' => 'Total', 'value' => number_format((float) $po->total_amount, 2, ',', '.')],
                ['label' => 'Jumlah baris', 'value' => (string) $po->lines->count()],
                ['label' => 'Catatan', 'value' => $po->notes ?: '—'],
            ],
            'lines_title' => 'Baris PO',
            'line_columns' => ['Item', 'Qty', 'Harga', 'Subtotal'],
            'lines' => $po->lines->map(fn ($line) => [
                ($line->item?->sku ?? '').' — '.($line->item?->name ?? ''),
                $this->formatQty((float) $line->qty_ordered),
                number_format((float) $line->unit_price, 2, ',', '.'),
                number_format((float) $line->line_total, 2, ',', '.'),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function approvalDemoContext(ApprovalRequest $item): ?array
    {
        $demo = ApprovalDemo::query()->find($item->document_id);

        if (! $demo) {
            return null;
        }

        return [
            'kind' => 'approval_demo',
            'title' => $demo->title,
            'purpose' => 'Dokumen uji alur approval.',
            'url' => route('approval-demos.index'),
            'url_label' => 'Buka Uji Approval',
            'summary' => [
                ['label' => 'Judul', 'value' => $demo->title],
                ['label' => 'Status dokumen', 'value' => $demo->status],
                ['label' => 'Amount', 'value' => number_format((float) $demo->amount, 2, ',', '.')],
            ],
            'lines_title' => null,
            'line_columns' => [],
            'lines' => [],
        ];
    }

    private function documentTypeLabel(string $type): string
    {
        return match ($type) {
            StockOpname::DOCUMENT_TYPE => 'Stock Opname',
            PurchaseOrder::DOCUMENT_TYPE => 'Purchase Order',
            BorrowRequest::DOCUMENT_TYPE => 'Peminjaman',
            ApprovalDemo::DOCUMENT_TYPE => 'Uji Approval',
            'asset_dispose' => 'Asset Dispose',
            default => $type,
        };
    }

    private function formatQty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}
