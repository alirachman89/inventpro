<?php

namespace App\Services;

use App\Models\AssetUnit;
use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class BorrowService
{
    public function __construct(
        private readonly DocumentNumberService $documentNumbers,
        private readonly ApprovalEngine $approvalEngine,
        private readonly AssetStatusService $assetStatusService,
        private readonly StockService $stockService,
        private readonly AuditLogger $auditLogger,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): BorrowRequest
    {
        return DB::transaction(function () use ($data, $actor) {
            $borrow = BorrowRequest::query()->create([
                'number' => $this->documentNumbers->next('prefix_borrow', 'BRW'),
                'status' => 'draft',
                'borrower_user_id' => $data['borrower_user_id'],
                'client_id' => $data['client_id'],
                'borrow_date' => $data['borrow_date'],
                'due_date' => $data['due_date'],
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncLines($borrow, $data['lines'] ?? []);

            $this->auditLogger->log(
                action: 'created',
                module: 'borrows',
                description: "Peminjaman {$borrow->number} dibuat",
                auditable: $borrow,
                actor: $actor,
            );

            return $borrow->fresh(['lines.item', 'lines.assetUnit', 'borrower', 'client']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BorrowRequest $borrow, array $data, User $actor): BorrowRequest
    {
        if (! $borrow->canEdit()) {
            throw ValidationException::withMessages([
                'borrow' => 'Peminjaman tidak dapat diubah.',
            ]);
        }

        return DB::transaction(function () use ($borrow, $data, $actor) {
            $borrow->update([
                'borrower_user_id' => $data['borrower_user_id'],
                'client_id' => $data['client_id'],
                'borrow_date' => $data['borrow_date'],
                'due_date' => $data['due_date'],
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $borrow->lines()->delete();
            $this->syncLines($borrow, $data['lines'] ?? []);

            $this->auditLogger->log(
                action: 'updated',
                module: 'borrows',
                description: "Peminjaman {$borrow->number} diperbarui",
                auditable: $borrow,
                actor: $actor,
            );

            return $borrow->fresh(['lines.item', 'lines.assetUnit', 'borrower', 'client']);
        });
    }

    public function submit(BorrowRequest $borrow, User $actor): BorrowRequest
    {
        if (! $borrow->canSubmit()) {
            throw ValidationException::withMessages([
                'borrow' => 'Peminjaman belum siap diajukan.',
            ]);
        }

        $this->approvalEngine->submit(
            BorrowRequest::DOCUMENT_TYPE,
            $borrow,
            $actor,
            $borrow->number,
        );

        return $borrow->fresh();
    }

    public function checkout(BorrowRequest $borrow, User $actor): BorrowRequest
    {
        if (! $borrow->canCheckout()) {
            throw ValidationException::withMessages([
                'borrow' => 'Hanya peminjaman approved yang dapat di-checkout.',
            ]);
        }

        return DB::transaction(function () use ($borrow, $actor) {
            $borrow->load(['lines.item', 'lines.assetUnit', 'borrower']);

            foreach ($borrow->lines as $index => $line) {
                try {
                    if ($line->asset_unit_id) {
                        $asset = AssetUnit::query()->lockForUpdate()->findOrFail($line->asset_unit_id);
                        $this->assetStatusService->checkoutBorrow(
                            $asset,
                            $borrow->borrower,
                            $borrow->client_id,
                            $actor,
                            "Checkout {$borrow->number}",
                        );

                        $line->update([
                            'qty_checked_out' => 1,
                            'from_location_id' => $asset->location_id,
                            'from_rack_id' => $asset->rack_id,
                        ]);
                    } else {
                        $item = Item::query()->findOrFail($line->item_id);
                        $rack = $this->stockService->resolveRack(
                            $line->from_location_id,
                            $line->from_rack_id,
                        );

                        $this->stockService->adjust(
                            item: $item,
                            locationId: $line->from_location_id,
                            rackId: $rack->id,
                            qtyDelta: -((float) $line->qty),
                            movementType: 'borrow_out',
                            actor: $actor,
                            notes: "BRW {$borrow->number}",
                            referenceType: BorrowRequest::class,
                            referenceId: $borrow->id,
                        );

                        $line->update([
                            'qty_checked_out' => (float) $line->qty,
                            'from_rack_id' => $rack->id,
                        ]);
                    }
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages([
                        "lines.{$index}" => $e->getMessage(),
                    ]);
                }
            }

            $borrow->update([
                'status' => 'checked_out',
                'checked_out_at' => now(),
            ]);

            $this->notifications->sendToUsers(
                [$borrow->borrower_user_id],
                'borrow_request',
                'Peminjaman di-checkout',
                "{$borrow->number} sudah di-checkout. Jatuh tempo: {$borrow->due_date->format('Y-m-d')}.",
                route('admin.borrows.show', $borrow->id),
            );

            $this->auditLogger->log(
                action: 'checked_out',
                module: 'borrows',
                description: "Peminjaman {$borrow->number} di-checkout",
                auditable: $borrow,
                actor: $actor,
            );

            return $borrow->fresh(['lines.assetUnit', 'borrower', 'client']);
        });
    }

    /**
     * @param  list<array{id: string, qty_return?: float|int|string, condition_on_return?: ?string}>  $returns
     */
    public function returnItems(BorrowRequest $borrow, array $returns, User $actor): BorrowRequest
    {
        if (! $borrow->canReturn()) {
            throw ValidationException::withMessages([
                'borrow' => 'Peminjaman ini tidak dalam status dapat di-return.',
            ]);
        }

        return DB::transaction(function () use ($borrow, $returns, $actor) {
            $borrow->load(['lines.item', 'lines.assetUnit']);
            $byId = collect($returns)->keyBy('id');

            foreach ($borrow->lines as $index => $line) {
                $input = $byId->get($line->id);
                if (! $input) {
                    continue;
                }

                $outstanding = $line->outstandingQty();
                if ($outstanding <= 0) {
                    continue;
                }

                $qtyReturn = isset($input['qty_return']) ? (float) $input['qty_return'] : $outstanding;
                if ($qtyReturn <= 0) {
                    continue;
                }
                if ($qtyReturn > $outstanding + 0.0001) {
                    throw ValidationException::withMessages([
                        "returns.{$index}.qty_return" => 'Qty return melebihi sisa pinjam.',
                    ]);
                }

                try {
                    if ($line->asset_unit_id) {
                        $asset = AssetUnit::query()->lockForUpdate()->findOrFail($line->asset_unit_id);
                        $this->assetStatusService->returnBorrow(
                            $asset,
                            $actor,
                            "Return {$borrow->number}",
                            $input['condition_on_return'] ?? 'good',
                        );
                        $line->update([
                            'qty_returned' => (float) $line->qty_returned + 1,
                            'condition_on_return' => $input['condition_on_return'] ?? 'good',
                        ]);
                    } else {
                        $item = Item::query()->findOrFail($line->item_id);
                        $this->stockService->adjust(
                            item: $item,
                            locationId: $line->from_location_id,
                            rackId: $line->from_rack_id,
                            qtyDelta: $qtyReturn,
                            movementType: 'borrow_return',
                            actor: $actor,
                            notes: "BRW {$borrow->number}",
                            condition: $input['condition_on_return'] ?? 'good',
                            referenceType: BorrowRequest::class,
                            referenceId: $borrow->id,
                        );
                        $line->update([
                            'qty_returned' => (float) $line->qty_returned + $qtyReturn,
                            'condition_on_return' => $input['condition_on_return'] ?? 'good',
                        ]);
                    }
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages([
                        "returns.{$index}" => $e->getMessage(),
                    ]);
                }
            }

            $borrow->load('lines');
            $totalOut = (float) $borrow->lines->sum('qty_checked_out');
            $totalRet = (float) $borrow->lines->sum('qty_returned');

            if ($totalRet + 0.0001 >= $totalOut) {
                $borrow->update([
                    'status' => 'returned',
                    'returned_at' => now(),
                ]);
            } else {
                $borrow->update(['status' => 'partially_returned']);
            }

            $this->auditLogger->log(
                action: 'returned',
                module: 'borrows',
                description: "Return peminjaman {$borrow->number}",
                auditable: $borrow,
                actor: $actor,
            );

            return $borrow->fresh(['lines.assetUnit', 'borrower', 'client']);
        });
    }

    public function notifyOverdue(BorrowRequest $borrow): void
    {
        if (! $borrow->isOverdue()) {
            return;
        }

        $this->notifications->sendToUsers(
            array_filter([$borrow->borrower_user_id, $borrow->created_by]),
            'borrow_overdue',
            'Peminjaman overdue',
            "{$borrow->number} melewati jatuh tempo {$borrow->due_date->format('Y-m-d')}.",
            route('admin.borrows.show', $borrow->id),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(BorrowRequest $borrow, array $lines): void
    {
        $rows = collect($lines)->filter(function ($line) {
            return ! empty($line['asset_unit_id']) || (! empty($line['item_id']) && (float) ($line['qty'] ?? 0) > 0);
        })->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Minimal satu baris peminjaman.',
            ]);
        }

        foreach ($rows as $index => $input) {
            if (! empty($input['asset_unit_id'])) {
                $asset = AssetUnit::query()->with('item')->find($input['asset_unit_id']);
                if (! $asset || $asset->status !== 'available') {
                    throw ValidationException::withMessages([
                        "lines.{$index}.asset_unit_id" => 'Unit asset harus available.',
                    ]);
                }

                $borrow->lines()->create([
                    'item_id' => $asset->item_id,
                    'asset_unit_id' => $asset->id,
                    'qty' => 1,
                    'from_location_id' => $asset->location_id,
                    'from_rack_id' => $asset->rack_id,
                    'notes' => $input['notes'] ?? null,
                ]);

                continue;
            }

            if (empty($input['from_location_id'])) {
                throw ValidationException::withMessages([
                    "lines.{$index}.from_location_id" => 'Lokasi sumber wajib untuk item consumable.',
                ]);
            }

            $borrow->lines()->create([
                'item_id' => $input['item_id'],
                'asset_unit_id' => null,
                'qty' => (float) $input['qty'],
                'from_location_id' => $input['from_location_id'],
                'from_rack_id' => $input['from_rack_id'] ?? null,
                'notes' => $input['notes'] ?? null,
            ]);
        }
    }
}
