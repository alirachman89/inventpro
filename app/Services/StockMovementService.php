<?php

namespace App\Services;

use App\Models\AssetUnit;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockMovementService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly DocumentNumberService $documentNumbers,
        private readonly AuditLogger $auditLogger,
        private readonly AssetStatusService $assetStatusService,
    ) {}

    /**
     * @param  array{
     *   type: string,
     *   reason: string,
     *   movement_date: string,
     *   client_id?: ?string,
     *   notes?: ?string,
     *   lines: list<array<string, mixed>>
     * }  $data
     */
    public function create(array $data, User $actor): StockMovement
    {
        $type = $data['type'];
        $lines = collect($data['lines'] ?? [])->filter(fn ($line) => (float) ($line['qty'] ?? 0) > 0)->values();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Minimal satu baris dengan qty > 0.',
            ]);
        }

        if ($type === 'out' && ($data['reason'] ?? '') === 'issue_to_client' && empty($data['client_id'])) {
            throw ValidationException::withMessages([
                'client_id' => 'Client wajib diisi untuk reason issue_to_client.',
            ]);
        }

        return DB::transaction(function () use ($data, $actor, $type, $lines) {
            $movement = StockMovement::query()->create([
                'number' => $this->documentNumbers->next('prefix_movement', 'MOV'),
                'type' => $type,
                'reason' => $data['reason'],
                'movement_date' => $data['movement_date'],
                'client_id' => $data['client_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($lines as $index => $input) {
                $this->processLine($movement, $input, $actor, $index);
            }

            $this->auditLogger->log(
                action: 'created',
                module: 'stock_movements',
                description: "Mutasi {$movement->number} ({$movement->type}) dibuat",
                auditable: $movement,
                newValues: [
                    'type' => $movement->type,
                    'reason' => $movement->reason,
                    'lines' => $lines->count(),
                ],
                actor: $actor,
            );

            return $movement->fresh(['lines.item', 'client', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function processLine(StockMovement $movement, array $input, User $actor, int $index): void
    {
        $item = Item::query()->find($input['item_id'] ?? null);

        if (! $item) {
            throw ValidationException::withMessages([
                "lines.{$index}.item_id" => 'Item tidak valid.',
            ]);
        }

        $qty = (float) $input['qty'];
        $condition = $input['condition'] ?? 'good';

        try {
            if ($movement->type === 'in') {
                $toRack = $this->stockService->resolveRack(
                    $input['to_location_id'],
                    $input['to_rack_id'] ?? null,
                );

                $movement->lines()->create([
                    'item_id' => $item->id,
                    'qty' => $qty,
                    'condition' => $condition,
                    'to_location_id' => $input['to_location_id'],
                    'to_rack_id' => $toRack->id,
                    'notes' => $input['notes'] ?? null,
                ]);

                $this->stockService->adjust(
                    item: $item,
                    locationId: $input['to_location_id'],
                    rackId: $toRack->id,
                    qtyDelta: $qty,
                    movementType: 'stock_in',
                    actor: $actor,
                    notes: "MOV {$movement->number}",
                    condition: $condition,
                    referenceType: StockMovement::class,
                    referenceId: $movement->id,
                );

                return;
            }

            if ($movement->type === 'out') {
                $fromRack = $this->stockService->resolveRack(
                    $input['from_location_id'],
                    $input['from_rack_id'] ?? null,
                );

                $movement->lines()->create([
                    'item_id' => $item->id,
                    'qty' => $qty,
                    'condition' => $condition,
                    'from_location_id' => $input['from_location_id'],
                    'from_rack_id' => $fromRack->id,
                    'notes' => $input['notes'] ?? null,
                ]);

                $this->stockService->adjust(
                    item: $item,
                    locationId: $input['from_location_id'],
                    rackId: $fromRack->id,
                    qtyDelta: -$qty,
                    movementType: 'stock_out',
                    actor: $actor,
                    notes: "MOV {$movement->number}",
                    condition: $condition,
                    referenceType: StockMovement::class,
                    referenceId: $movement->id,
                );

                if (! empty($input['asset_unit_id']) && $movement->client_id) {
                    AssetUnit::query()->whereKey($input['asset_unit_id'])->update([
                        'current_client_id' => $movement->client_id,
                    ]);
                }

                return;
            }

            // transfer
            $fromRack = $this->stockService->resolveRack(
                $input['from_location_id'],
                $input['from_rack_id'] ?? null,
            );
            $toRack = $this->stockService->resolveRack(
                $input['to_location_id'],
                $input['to_rack_id'] ?? null,
            );

            if ($fromRack->id === $toRack->id) {
                throw ValidationException::withMessages([
                    "lines.{$index}.to_rack_id" => 'Lokasi/rak tujuan harus berbeda dari sumber.',
                ]);
            }

            $line = $movement->lines()->create([
                'item_id' => $item->id,
                'qty' => $qty,
                'condition' => $condition,
                'from_location_id' => $input['from_location_id'],
                'from_rack_id' => $fromRack->id,
                'to_location_id' => $input['to_location_id'],
                'to_rack_id' => $toRack->id,
                'asset_unit_id' => $input['asset_unit_id'] ?? null,
                'notes' => $input['notes'] ?? null,
            ]);

            if (! empty($input['asset_unit_id'])) {
                $asset = AssetUnit::query()->findOrFail($input['asset_unit_id']);
                $this->assetStatusService->transfer(
                    $asset,
                    $input['to_location_id'],
                    $toRack->id,
                    $actor,
                    "Transfer via {$movement->number}",
                );
            } else {
                $this->stockService->adjust(
                    item: $item,
                    locationId: $input['from_location_id'],
                    rackId: $fromRack->id,
                    qtyDelta: -$qty,
                    movementType: 'transfer_out',
                    actor: $actor,
                    notes: "MOV {$movement->number}",
                    condition: $condition,
                    referenceType: StockMovement::class,
                    referenceId: $movement->id,
                );

                $this->stockService->adjust(
                    item: $item,
                    locationId: $input['to_location_id'],
                    rackId: $toRack->id,
                    qtyDelta: $qty,
                    movementType: 'transfer_in',
                    actor: $actor,
                    notes: "MOV {$movement->number}",
                    condition: $condition,
                    referenceType: StockMovement::class,
                    referenceId: $movement->id,
                );
            }

            unset($line);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                "lines.{$index}.qty" => $e->getMessage(),
            ]);
        }
    }
}
