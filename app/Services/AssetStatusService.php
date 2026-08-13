<?php

namespace App\Services;

use App\Models\AssetStatusHistory;
use App\Models\AssetUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssetStatusService
{
    public function changeStatus(AssetUnit $asset, string $toStatus, ?User $actor = null, ?string $notes = null): AssetUnit
    {
        if (! in_array($toStatus, AssetUnit::STATUSES, true)) {
            throw new InvalidArgumentException('Status tidak valid.');
        }

        if ($asset->status === $toStatus) {
            throw new InvalidArgumentException('Status sudah sama.');
        }

        return DB::transaction(function () use ($asset, $toStatus, $actor, $notes) {
            $from = $asset->status;
            $asset->status = $toStatus;

            if (in_array($toStatus, ['damaged', 'quarantine'], true)) {
                $asset->condition = $toStatus;
            } elseif ($toStatus === 'available') {
                $asset->condition = 'good';
            }

            $asset->save();

            AssetStatusHistory::query()->create([
                'asset_unit_id' => $asset->id,
                'from_status' => $from,
                'to_status' => $toStatus,
                'changed_by' => $actor?->id,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            return $asset->fresh(['location', 'rack', 'holder']);
        });
    }

    public function transfer(
        AssetUnit $asset,
        string $toLocationId,
        string $toRackId,
        ?User $actor = null,
        ?string $notes = null,
    ): AssetUnit {
        if ($asset->status !== 'available') {
            throw new InvalidArgumentException('Hanya unit asset berstatus available yang bisa ditransfer.');
        }

        return DB::transaction(function () use ($asset, $toLocationId, $toRackId, $actor, $notes) {
            $this->changeStatus($asset, 'in_transit', $actor, $notes ?? 'Mulai transfer');

            $asset->refresh();
            $asset->location_id = $toLocationId;
            $asset->rack_id = $toRackId;
            $asset->save();

            return $this->changeStatus(
                $asset,
                'available',
                $actor,
                $notes ?? 'Selesai transfer ke lokasi tujuan',
            );
        });
    }

    public function checkoutBorrow(
        AssetUnit $asset,
        User $borrower,
        string $clientId,
        ?User $actor = null,
        ?string $notes = null,
    ): AssetUnit {
        if ($asset->status !== 'available') {
            throw new InvalidArgumentException("Unit {$asset->asset_tag} tidak available untuk dipinjam.");
        }

        return DB::transaction(function () use ($asset, $borrower, $clientId, $actor, $notes) {
            $asset->current_holder_user_id = $borrower->id;
            $asset->current_client_id = $clientId;
            $asset->save();

            return $this->changeStatus(
                $asset->fresh(),
                'borrowed',
                $actor,
                $notes ?? 'Checkout peminjaman',
            );
        });
    }

    public function returnBorrow(
        AssetUnit $asset,
        ?User $actor = null,
        ?string $notes = null,
        ?string $condition = null,
    ): AssetUnit {
        if ($asset->status !== 'borrowed') {
            throw new InvalidArgumentException("Unit {$asset->asset_tag} tidak dalam status borrowed.");
        }

        return DB::transaction(function () use ($asset, $actor, $notes, $condition) {
            $asset->current_holder_user_id = null;
            $asset->current_client_id = null;
            if ($condition) {
                $asset->condition = $condition;
            }
            $asset->save();

            return $this->changeStatus(
                $asset->fresh(),
                'available',
                $actor,
                $notes ?? 'Return peminjaman',
            );
        });
    }
}
