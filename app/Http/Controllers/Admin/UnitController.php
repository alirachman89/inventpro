<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUnitRequest;
use App\Http\Requests\Admin\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $units = Unit::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Unit $unit) => [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
                'symbol' => $unit->symbol,
                'type' => $unit->type,
                'is_active' => $unit->is_active,
                'sort_order' => $unit->sort_order,
            ]);

        return Inertia::render('Admin/Units/Index', [
            'units' => $units,
            'filters' => ['search' => $search],
            'types' => Unit::TYPES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Units/Form', [
            'unit' => null,
            'types' => Unit::TYPES,
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $unit = Unit::query()->create($data);

        $this->auditLogger->log(
            action: 'created',
            module: 'units',
            description: "Satuan {$unit->code} dibuat",
            auditable: $unit,
            newValues: $unit->only(['code', 'name', 'symbol', 'type', 'is_active', 'sort_order']),
        );

        return redirect()
            ->route('admin.units.index')
            ->with('success', 'Satuan berhasil dibuat.');
    }

    public function edit(Unit $unit): Response
    {
        return Inertia::render('Admin/Units/Form', [
            'unit' => $unit->only(['id', 'code', 'name', 'symbol', 'type', 'description', 'is_active', 'sort_order']),
            'types' => Unit::TYPES,
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $old = $unit->only(['code', 'name', 'symbol', 'type', 'description', 'is_active', 'sort_order']);
        $unit->update($request->validated());

        $this->auditLogger->log(
            action: 'updated',
            module: 'units',
            description: "Satuan {$unit->code} diperbarui",
            auditable: $unit,
            oldValues: $old,
            newValues: $unit->only(['code', 'name', 'symbol', 'type', 'description', 'is_active', 'sort_order']),
        );

        return redirect()
            ->route('admin.units.index')
            ->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $old = $unit->only(['code', 'name', 'type', 'is_active']);
        $code = $unit->code;
        $unit->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'units',
            description: "Satuan {$code} dihapus",
            oldValues: $old,
        );

        return redirect()
            ->route('admin.units.index')
            ->with('success', 'Satuan berhasil dihapus.');
    }
}
