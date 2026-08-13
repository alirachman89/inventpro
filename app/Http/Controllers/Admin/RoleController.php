<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->with('permissions:id,name')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'is_superadmin' => $role->name === User::SUPERADMIN_ROLE,
                'permissions' => $role->permissions->pluck('name'),
            ]);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Roles/Form', [
            'role' => null,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        $this->auditLogger->log(
            action: 'created',
            module: 'roles',
            description: "Role {$role->name} dibuat",
            auditable: $role,
            newValues: [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name'),
            ],
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Admin/Roles/Form', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_superadmin' => $role->name === User::SUPERADMIN_ROLE,
            ],
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => $role->permissions()->pluck('name'),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $old = [
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('name')->values(),
        ];

        if ($role->name !== User::SUPERADMIN_ROLE) {
            $role->name = $request->validated('name');
            $role->save();
        }

        $role->syncPermissions($request->validated('permissions', []));

        $this->auditLogger->log(
            action: 'updated',
            module: 'roles',
            description: "Role {$role->name} diperbarui",
            auditable: $role,
            oldValues: $old,
            newValues: [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->values(),
            ],
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === User::SUPERADMIN_ROLE) {
            return back()->with('error', 'Role superadmin tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Role masih digunakan oleh user.');
        }

        $old = [
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('name')->values(),
        ];

        $name = $role->name;
        $role->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'roles',
            description: "Role {$name} dihapus",
            oldValues: $old,
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    /**
     * @return array<string, list<string>>
     */
    private function permissionGroups(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->groupBy(fn (string $name) => explode('.', $name)[0] ?? 'other')
            ->map(fn ($items) => $items->values()->all())
            ->all();
    }
}
