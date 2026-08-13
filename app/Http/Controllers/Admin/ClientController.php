<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        $status = $request->string('status')->toString();

        $clients = Client::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Client $client) => [
                'id' => $client->id,
                'code' => $client->code,
                'name' => $client->name,
                'type' => $client->type,
                'contact_person' => $client->contact_person,
                'phone' => $client->phone,
                'is_active' => $client->is_active,
            ]);

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
            'types' => Client::TYPES,
            'typeLabels' => $this->typeLabels(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Clients/Form', [
            'client' => null,
            'types' => Client::TYPES,
            'typeLabels' => $this->typeLabels(),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::query()->create($request->validated());

        $this->auditLogger->log(
            action: 'created',
            module: 'clients',
            description: "Client {$client->code} dibuat",
            auditable: $client,
            newValues: $client->only(['code', 'name', 'type', 'is_active']),
        );

        return redirect()
            ->route('admin.clients.show', $client)
            ->with('success', 'Client berhasil dibuat.');
    }

    public function show(Client $client): Response
    {
        $assets = $client->assetUnits()
            ->with(['item:id,sku,name', 'holder:id,name'])
            ->orderBy('asset_tag')
            ->get()
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'asset_tag' => $asset->asset_tag,
                'item' => $asset->item?->only(['sku', 'name']),
                'status' => $asset->status,
                'holder' => $asset->holder?->name,
            ]);

        return Inertia::render('Admin/Clients/Show', [
            'client' => $client->only([
                'id', 'code', 'name', 'type', 'contact_person', 'email',
                'phone', 'address', 'tax_id', 'notes', 'is_active',
            ]),
            'typeLabels' => $this->typeLabels(),
            'activeBorrows' => [], // Phase 10
            'borrowHistory' => [], // Phase 10
            'assetsAtClient' => $assets,
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Admin/Clients/Form', [
            'client' => $client->only([
                'id', 'code', 'name', 'type', 'contact_person', 'email',
                'phone', 'address', 'tax_id', 'notes', 'is_active',
            ]),
            'types' => Client::TYPES,
            'typeLabels' => $this->typeLabels(),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $old = $client->only(['code', 'name', 'type', 'is_active', 'contact_person']);
        $client->update($request->validated());

        $this->auditLogger->log(
            action: 'updated',
            module: 'clients',
            description: "Client {$client->code} diperbarui",
            auditable: $client,
            oldValues: $old,
            newValues: $client->only(['code', 'name', 'type', 'is_active', 'contact_person']),
        );

        return redirect()
            ->route('admin.clients.show', $client)
            ->with('success', 'Client berhasil diperbarui.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->assetUnits()->exists()) {
            return back()->with('error', 'Client masih dipakai unit asset. Nonaktifkan saja, jangan hapus.');
        }

        $old = $client->only(['code', 'name', 'type', 'is_active']);
        $code = $client->code;
        $client->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'clients',
            description: "Client {$code} dihapus",
            oldValues: $old,
        );

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Client berhasil dihapus.');
    }

    /**
     * @return array<string, string>
     */
    private function typeLabels(): array
    {
        return [
            'company' => 'Perusahaan',
            'project_site' => 'Site / Proyek',
            'individual' => 'Individu',
            'internal_unit' => 'Unit internal',
            'other' => 'Lainnya',
        ];
    }
}
