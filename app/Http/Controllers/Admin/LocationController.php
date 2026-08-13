<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Models\Location;
use App\Services\AuditLogger;
use App\Services\LocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly LocationService $locationService,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $locations = Location::query()
            ->withCount('racks')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Location $location) => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'type' => $location->type,
                'is_active' => $location->is_active,
                'racks_count' => $location->racks_count,
            ]);

        return Inertia::render('Admin/Locations/Index', [
            'locations' => $locations,
            'filters' => ['search' => $search],
            'types' => Location::TYPES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Locations/Form', [
            'location' => null,
            'types' => Location::TYPES,
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $location = $this->locationService->createWithGeneralRack($request->validated());

        $this->auditLogger->log(
            action: 'created',
            module: 'locations',
            description: "Lokasi {$location->code} dibuat (rak GENERAL otomatis)",
            auditable: $location,
            newValues: $location->only(['code', 'name', 'type', 'address', 'is_active']),
        );

        return redirect()
            ->route('admin.locations.show', $location)
            ->with('success', 'Lokasi berhasil dibuat. Rak GENERAL sudah ditambahkan.');
    }

    public function show(Location $location): Response
    {
        $location->load(['racks' => fn ($q) => $q->orderByDesc('is_default')->orderBy('code')]);

        return Inertia::render('Admin/Locations/Show', [
            'location' => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'type' => $location->type,
                'address' => $location->address,
                'is_active' => $location->is_active,
            ],
            'racks' => $location->racks->map(fn ($rack) => [
                'id' => $rack->id,
                'code' => $rack->code,
                'name' => $rack->name,
                'label' => $rack->label,
                'description' => $rack->description,
                'is_default' => $rack->is_default,
                'is_active' => $rack->is_active,
                'is_general' => $rack->isGeneral(),
            ]),
            'types' => Location::TYPES,
        ]);
    }

    public function edit(Location $location): Response
    {
        return Inertia::render('Admin/Locations/Form', [
            'location' => $location->only(['id', 'code', 'name', 'type', 'address', 'is_active']),
            'types' => Location::TYPES,
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $old = $location->only(['code', 'name', 'type', 'address', 'is_active']);
        $location->update($request->validated());
        $this->locationService->ensureGeneralRack($location);

        $this->auditLogger->log(
            action: 'updated',
            module: 'locations',
            description: "Lokasi {$location->code} diperbarui",
            auditable: $location,
            oldValues: $old,
            newValues: $location->only(['code', 'name', 'type', 'address', 'is_active']),
        );

        return redirect()
            ->route('admin.locations.show', $location)
            ->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $old = $location->only(['code', 'name', 'type', 'is_active']);
        $code = $location->code;

        $location->racks()->delete();
        $location->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'locations',
            description: "Lokasi {$code} dihapus",
            oldValues: $old,
        );

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Lokasi berhasil dihapus.');
    }
}
