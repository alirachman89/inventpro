<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorRequest;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Models\Vendor;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $vendors = Vendor::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Vendor $vendor) => [
                'id' => $vendor->id,
                'code' => $vendor->code,
                'name' => $vendor->name,
                'contact_person' => $vendor->contact_person,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'is_active' => $vendor->is_active,
            ]);

        return Inertia::render('Admin/Vendors/Index', [
            'vendors' => $vendors,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Vendors/Form', [
            'vendor' => null,
        ]);
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        $vendor = Vendor::query()->create($request->validated());

        $this->auditLogger->log(
            action: 'created',
            module: 'vendors',
            description: "Vendor {$vendor->code} dibuat",
            auditable: $vendor,
            newValues: $vendor->only(['code', 'name', 'is_active', 'email', 'phone']),
        );

        return redirect()
            ->route('admin.vendors.index')
            ->with('success', 'Vendor berhasil dibuat.');
    }

    public function show(Vendor $vendor): Response
    {
        $orders = $vendor->purchaseOrders()
            ->latest()
            ->limit(20)
            ->get(['id', 'number', 'status', 'order_date', 'total_amount'])
            ->map(fn ($po) => [
                'id' => $po->id,
                'number' => $po->number,
                'status' => $po->status,
                'order_date' => $po->order_date?->format('Y-m-d'),
                'total_amount' => (float) $po->total_amount,
            ]);

        return Inertia::render('Admin/Vendors/Show', [
            'vendor' => $vendor->only([
                'id', 'code', 'name', 'tax_id', 'contact_person', 'email',
                'phone', 'address', 'notes', 'is_active',
            ]),
            'purchaseHistory' => $orders,
        ]);
    }

    public function edit(Vendor $vendor): Response
    {
        return Inertia::render('Admin/Vendors/Form', [
            'vendor' => $vendor->only([
                'id', 'code', 'name', 'tax_id', 'contact_person', 'email',
                'phone', 'address', 'notes', 'is_active',
            ]),
        ]);
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $old = $vendor->only(['code', 'name', 'tax_id', 'contact_person', 'email', 'phone', 'is_active']);
        $vendor->update($request->validated());

        $this->auditLogger->log(
            action: 'updated',
            module: 'vendors',
            description: "Vendor {$vendor->code} diperbarui",
            auditable: $vendor,
            oldValues: $old,
            newValues: $vendor->only(['code', 'name', 'tax_id', 'contact_person', 'email', 'phone', 'is_active']),
        );

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('success', 'Vendor berhasil diperbarui.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $old = $vendor->only(['code', 'name', 'is_active']);
        $code = $vendor->code;
        $vendor->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'vendors',
            description: "Vendor {$code} dihapus",
            oldValues: $old,
        );

        return redirect()
            ->route('admin.vendors.index')
            ->with('success', 'Vendor berhasil dihapus.');
    }
}
