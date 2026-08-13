<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockOpnameService
{
    public function __construct(
        private readonly DocumentNumberService $documentNumbers,
        private readonly StockService $stockService,
        private readonly ApprovalEngine $approvalEngine,
        private readonly AuditLogger $auditLogger,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array{location_id: string, opname_date: string, pic_user_id?: ?string, notes?: ?string}  $data
     */
    public function create(array $data, User $actor): StockOpname
    {
        return DB::transaction(function () use ($data, $actor) {
            $opname = StockOpname::query()->create([
                'number' => $this->documentNumbers->next('prefix_opname', 'OPN'),
                'location_id' => $data['location_id'],
                'status' => 'draft',
                'opname_date' => $data['opname_date'],
                'pic_user_id' => $data['pic_user_id'] ?? $actor->id,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->generateLines($opname);

            $this->auditLogger->log(
                action: 'created',
                module: 'stock_opnames',
                description: "Sesi opname {$opname->number} dibuat",
                auditable: $opname,
                actor: $actor,
            );

            return $opname->fresh(['lines.item', 'lines.rack', 'location', 'pic', 'creator']);
        });
    }

    public function generateLines(StockOpname $opname): void
    {
        if (! $opname->canEdit()) {
            throw ValidationException::withMessages([
                'opname' => 'Sesi opname tidak dapat diubah.',
            ]);
        }

        $stocks = ItemStock::query()
            ->with('item:id,sku,name,item_type')
            ->where('location_id', $opname->location_id)
            ->where('qty_on_hand', '>', 0)
            ->whereHas('item', fn ($q) => $q->where('item_type', 'consumable')->where('is_active', true))
            ->orderBy('item_id')
            ->get();

        $existing = $opname->lines()->get()->keyBy(
            fn ($line) => "{$line->item_id}|{$line->rack_id}|{$line->condition}",
        );

        foreach ($stocks as $stock) {
            $key = "{$stock->item_id}|{$stock->rack_id}|{$stock->condition}";
            $line = $existing->get($key);

            if ($line) {
                $line->qty_system = (float) $stock->qty_on_hand;
                if ($line->qty_counted !== null) {
                    $line->recalculateVariance();
                }
                $line->save();
                $existing->forget($key);

                continue;
            }

            $opname->lines()->create([
                'item_id' => $stock->item_id,
                'rack_id' => $stock->rack_id,
                'condition' => $stock->condition,
                'qty_system' => (float) $stock->qty_on_hand,
                'qty_counted' => null,
                'qty_variance' => null,
            ]);
        }
    }

    /**
     * @param  list<array{id: string, qty_counted: float|int|string|null, notes?: ?string}>  $lines
     */
    public function updateCounts(StockOpname $opname, array $lines, User $actor): StockOpname
    {
        if (! $opname->canEdit()) {
            throw ValidationException::withMessages([
                'opname' => 'Sesi opname tidak dapat diubah.',
            ]);
        }

        return DB::transaction(function () use ($opname, $lines, $actor) {
            foreach ($lines as $index => $input) {
                $line = $opname->lines()->whereKey($input['id'] ?? null)->first();

                if (! $line) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.id" => 'Baris opname tidak valid.',
                    ]);
                }

                $counted = $input['qty_counted'];
                $line->qty_counted = $counted === '' || $counted === null ? null : (float) $counted;
                $line->notes = $input['notes'] ?? $line->notes;
                $line->recalculateVariance();
                $line->save();
            }

            $this->auditLogger->log(
                action: 'updated',
                module: 'stock_opnames',
                description: "Qty fisik opname {$opname->number} diperbarui",
                auditable: $opname,
                actor: $actor,
            );

            return $opname->fresh(['lines.item', 'lines.rack', 'location']);
        });
    }

    public function submit(StockOpname $opname, User $actor): StockOpname
    {
        if (! $opname->canSubmit()) {
            throw ValidationException::withMessages([
                'opname' => 'Semua baris harus diisi qty fisik sebelum diajukan.',
            ]);
        }

        // Tanpa selisih: langsung selesai tanpa approval
        if ($opname->varianceCount() === 0) {
            $opname->update([
                'status' => 'posted',
                'submitted_at' => now(),
                'approved_at' => now(),
                'posted_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'posted',
                module: 'stock_opnames',
                description: "Opname {$opname->number} selesai tanpa selisih",
                auditable: $opname,
                actor: $actor,
            );

            return $opname->fresh();
        }

        $this->approvalEngine->submit(
            StockOpname::DOCUMENT_TYPE,
            $opname,
            $actor,
            $opname->number,
        );

        return $opname->fresh();
    }

    public function post(StockOpname $opname, User $actor): StockOpname
    {
        if (! $opname->canPost()) {
            throw ValidationException::withMessages([
                'opname' => 'Hanya opname berstatus approved yang dapat diposting.',
            ]);
        }

        return DB::transaction(function () use ($opname, $actor) {
            $opname->load(['lines.item']);

            foreach ($opname->lines as $line) {
                $variance = (float) ($line->qty_variance ?? 0);

                if ($variance == 0.0) {
                    continue;
                }

                $item = Item::query()->findOrFail($line->item_id);

                try {
                    $this->stockService->adjust(
                        item: $item,
                        locationId: $opname->location_id,
                        rackId: $line->rack_id,
                        qtyDelta: $variance,
                        movementType: 'opname_adjustment',
                        actor: $actor,
                        notes: "OPN {$opname->number}",
                        condition: $line->condition,
                        referenceType: StockOpname::class,
                        referenceId: $opname->id,
                    );
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages([
                        'opname' => "Gagal posting baris {$item->sku}: {$e->getMessage()}",
                    ]);
                }
            }

            $opname->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'posted',
                module: 'stock_opnames',
                description: "Opname {$opname->number} diposting ke ledger",
                auditable: $opname,
                actor: $actor,
            );

            if ($opname->created_by) {
                $this->notifications->sendToUsers(
                    [$opname->created_by],
                    'stock_opname',
                    'Opname diposting',
                    "Sesi {$opname->number} telah diposting. Stok disesuaikan sesuai selisih.",
                    route('admin.stock-opnames.show', $opname->id),
                );
            }

            return $opname->fresh(['lines.item', 'lines.rack', 'location']);
        });
    }
}
