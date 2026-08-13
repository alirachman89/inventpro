<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRackRequest;
use App\Http\Requests\Admin\UpdateRackRequest;
use App\Models\Location;
use App\Models\Rack;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RackController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function create(Location $location): Response
    {
        return Inertia::render('Admin/Racks/Form', [
            'location' => $location->only(['id', 'code', 'name']),
            'rack' => null,
        ]);
    }

    public function store(StoreRackRequest $request, Location $location): RedirectResponse
    {
        $data = $request->validated();

        $rack = $location->racks()->create([
            ...$data,
            'is_default' => false,
        ]);

        $this->auditLogger->log(
            action: 'created',
            module: 'racks',
            description: "Rak {$rack->code} di lokasi {$location->code} dibuat",
            auditable: $rack,
            newValues: $rack->only(['location_id', 'code', 'name', 'label', 'is_default', 'is_active']),
        );

        return redirect()
            ->route('admin.locations.show', $location)
            ->with('success', 'Rak berhasil dibuat.');
    }

    public function edit(Rack $rack): Response
    {
        $rack->load('location:id,code,name');

        return Inertia::render('Admin/Racks/Form', [
            'location' => $rack->location->only(['id', 'code', 'name']),
            'rack' => [
                'id' => $rack->id,
                'code' => $rack->code,
                'name' => $rack->name,
                'label' => $rack->label,
                'description' => $rack->description,
                'is_active' => $rack->is_active,
                'is_general' => $rack->isGeneral(),
            ],
        ]);
    }

    public function update(UpdateRackRequest $request, Rack $rack): RedirectResponse
    {
        $old = $rack->only(['code', 'name', 'label', 'description', 'is_active']);
        $data = $request->validated();

        if ($rack->isGeneral()) {
            $data['code'] = Location::GENERAL_RACK_CODE;
            $data['is_default'] = true;
        }

        $rack->update($data);

        $this->auditLogger->log(
            action: 'updated',
            module: 'racks',
            description: "Rak {$rack->code} diperbarui",
            auditable: $rack,
            oldValues: $old,
            newValues: $rack->only(['code', 'name', 'label', 'description', 'is_active']),
        );

        return redirect()
            ->route('admin.locations.show', $rack->location_id)
            ->with('success', 'Rak berhasil diperbarui.');
    }

    public function destroy(Rack $rack): RedirectResponse
    {
        if ($rack->isGeneral()) {
            return back()->with('error', 'Rak GENERAL tidak dapat dihapus. Anda hanya dapat mengubah label.');
        }

        $locationId = $rack->location_id;
        $old = $rack->only(['location_id', 'code', 'name', 'label', 'is_active']);
        $code = $rack->code;
        $rack->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'racks',
            description: "Rak {$code} dihapus",
            oldValues: $old,
        );

        return redirect()
            ->route('admin.locations.show', $locationId)
            ->with('success', 'Rak berhasil dihapus.');
    }
}
