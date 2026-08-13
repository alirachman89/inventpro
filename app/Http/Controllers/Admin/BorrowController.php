<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReturnBorrowRequest;
use App\Http\Requests\Admin\StoreBorrowRequest;
use App\Http\Requests\Admin\UpdateBorrowRequest;
use App\Models\AssetUnit;
use App\Models\BorrowRequest;
use App\Models\Client;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BorrowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BorrowController extends Controller
{
    public function __construct(
        private readonly BorrowService $borrowService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $borrowerId = $request->string('borrower_user_id')->toString();
        $clientId = $request->string('client_id')->toString();
        $overdueOnly = $request->boolean('overdue');

        $borrows = BorrowRequest::query()
            ->with(['borrower:id,name', 'client:id,code,name', 'creator:id,name'])
            ->withCount('lines')
            ->when($search !== '', fn ($q) => $q->where('number', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($borrowerId !== '', fn ($q) => $q->where('borrower_user_id', $borrowerId))
            ->when($clientId !== '', fn ($q) => $q->where('client_id', $clientId))
            ->when($overdueOnly, function ($q) {
                $q->whereIn('status', ['checked_out', 'partially_returned'])
                    ->whereDate('due_date', '<', now()->toDateString());
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (BorrowRequest $borrow) => [
                'id' => $borrow->id,
                'number' => $borrow->number,
                'status' => $borrow->status,
                'is_overdue' => $borrow->isOverdue(),
                'borrow_date' => $borrow->borrow_date?->format('Y-m-d'),
                'due_date' => $borrow->due_date?->format('Y-m-d'),
                'borrower' => $borrow->borrower?->name,
                'client' => $borrow->client?->only(['code', 'name']),
                'lines_count' => $borrow->lines_count,
            ]);

        return Inertia::render('Admin/Borrows/Index', [
            'borrows' => $borrows,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'borrower_user_id' => $borrowerId,
                'client_id' => $clientId,
                'overdue' => $overdueOnly,
            ],
            'statuses' => BorrowRequest::STATUSES,
            'statusLabels' => $this->statusLabels(),
            'borrowers' => User::query()->orderBy('name')->get(['id', 'name']),
            'clients' => Client::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Borrows/Form', [
            'borrow' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreBorrowRequest $request): RedirectResponse
    {
        $borrow = $this->borrowService->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.borrows.show', $borrow)
            ->with('success', "Peminjaman {$borrow->number} dibuat.");
    }

    public function edit(BorrowRequest $borrow): Response|RedirectResponse
    {
        if (! $borrow->canEdit()) {
            return redirect()
                ->route('admin.borrows.show', $borrow)
                ->with('error', 'Peminjaman tidak dapat diedit.');
        }

        $borrow->load(['lines.assetUnit', 'lines.item']);

        return Inertia::render('Admin/Borrows/Form', [
            'borrow' => $this->transformBorrow($borrow),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateBorrowRequest $request, BorrowRequest $borrow): RedirectResponse
    {
        try {
            $this->borrowService->update($borrow, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('admin.borrows.show', $borrow)
            ->with('success', 'Peminjaman diperbarui.');
    }

    public function show(BorrowRequest $borrow): Response
    {
        $borrow->load([
            'borrower:id,name,email',
            'client:id,code,name',
            'creator:id,name',
            'lines.item:id,sku,name',
            'lines.assetUnit:id,asset_tag,serial_number,status,current_holder_user_id,current_client_id',
            'lines.assetUnit.holder:id,name',
            'lines.assetUnit.client:id,code,name',
            'lines.fromLocation:id,code,name',
            'lines.fromRack:id,code,label',
        ]);

        return Inertia::render('Admin/Borrows/Show', [
            'borrow' => $this->transformBorrow($borrow, detailed: true),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function submit(Request $request, BorrowRequest $borrow): RedirectResponse
    {
        abort_unless($request->user()?->can('borrows.submit'), 403);

        $this->borrowService->submit($borrow, $request->user());

        return redirect()
            ->route('admin.borrows.show', $borrow)
            ->with('success', 'Peminjaman diajukan untuk approval.');
    }

    public function checkout(Request $request, BorrowRequest $borrow): RedirectResponse
    {
        abort_unless($request->user()?->can('borrows.checkout'), 403);

        try {
            $this->borrowService->checkout($borrow, $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('admin.borrows.show', $borrow)
            ->with('success', 'Checkout berhasil. Asset di-set borrowed (holder + client).');
    }

    public function returnItems(ReturnBorrowRequest $request, BorrowRequest $borrow): RedirectResponse
    {
        try {
            $this->borrowService->returnItems(
                $borrow,
                $request->validated('returns'),
                $request->user(),
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('admin.borrows.show', $borrow)
            ->with('success', 'Return dicatat.');
    }

    public function destroy(BorrowRequest $borrow): RedirectResponse
    {
        if (! in_array($borrow->status, ['draft', 'rejected', 'cancelled'], true)) {
            return back()->with('error', 'Hanya draft/rejected yang dapat dihapus.');
        }

        $number = $borrow->number;
        $borrow->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'borrows',
            description: "Peminjaman {$number} dihapus",
        );

        return redirect()
            ->route('admin.borrows.index')
            ->with('success', 'Peminjaman dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'borrowers' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'clients' => Client::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'assetUnits' => AssetUnit::query()
                ->with(['item:id,sku,name', 'location:id,code', 'rack:id,code'])
                ->where('status', 'available')
                ->orderBy('asset_tag')
                ->get()
                ->map(fn (AssetUnit $unit) => [
                    'id' => $unit->id,
                    'asset_tag' => $unit->asset_tag,
                    'serial_number' => $unit->serial_number,
                    'item' => $unit->item?->only(['sku', 'name']),
                    'location_code' => $unit->location?->code,
                    'rack_code' => $unit->rack?->code,
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformBorrow(BorrowRequest $borrow, bool $detailed = false): array
    {
        $payload = [
            'id' => $borrow->id,
            'number' => $borrow->number,
            'status' => $borrow->status,
            'is_overdue' => $borrow->isOverdue(),
            'borrower_user_id' => $borrow->borrower_user_id,
            'client_id' => $borrow->client_id,
            'borrow_date' => $borrow->borrow_date?->format('Y-m-d'),
            'due_date' => $borrow->due_date?->format('Y-m-d'),
            'purpose' => $borrow->purpose,
            'notes' => $borrow->notes,
            'borrower' => $borrow->borrower?->only(['id', 'name', 'email']),
            'client' => $borrow->client?->only(['id', 'code', 'name']),
            'creator' => $borrow->creator?->name,
            'can_edit' => $borrow->canEdit(),
            'can_submit' => $borrow->canSubmit(),
            'can_checkout' => $borrow->canCheckout(),
            'can_return' => $borrow->canReturn(),
            'lines' => $borrow->lines->map(fn ($line) => [
                'id' => $line->id,
                'item_id' => $line->item_id,
                'asset_unit_id' => $line->asset_unit_id,
                'qty' => (float) $line->qty,
                'qty_checked_out' => (float) $line->qty_checked_out,
                'qty_returned' => (float) $line->qty_returned,
                'outstanding' => $line->outstandingQty(),
                'item' => $line->item?->only(['sku', 'name']),
                'asset_unit' => $line->assetUnit ? [
                    'asset_tag' => $line->assetUnit->asset_tag,
                    'serial_number' => $line->assetUnit->serial_number,
                    'status' => $line->assetUnit->status,
                    'holder' => $line->assetUnit->holder?->name,
                    'client' => $line->assetUnit->client?->only(['code', 'name']),
                ] : null,
                'from_location' => $line->fromLocation?->only(['code', 'name']),
                'from_rack' => $line->fromRack?->only(['code', 'label']),
                'condition_on_return' => $line->condition_on_return,
                'notes' => $line->notes,
            ]),
        ];

        if ($detailed) {
            $payload['submitted_at'] = $borrow->submitted_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');
            $payload['approved_at'] = $borrow->approved_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');
            $payload['checked_out_at'] = $borrow->checked_out_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');
            $payload['returned_at'] = $borrow->returned_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');
        }

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Menunggu approval',
            'approved' => 'Disetujui — siap checkout',
            'rejected' => 'Ditolak',
            'checked_out' => 'Dipinjam (checked out)',
            'partially_returned' => 'Return sebagian',
            'returned' => 'Selesai (returned)',
            'cancelled' => 'Dibatalkan',
        ];
    }
}
