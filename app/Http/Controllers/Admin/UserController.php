<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'is_superadmin' => $user->isSuperAdmin(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => null,
            'roles' => $this->roleOptions(),
            'selectedRoles' => [],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($data['roles'] ?? []);

        $this->auditLogger->log(
            action: 'created',
            module: 'users',
            description: "User {$user->email} dibuat",
            auditable: $user,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values(),
            ],
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_superadmin' => $user->isSuperAdmin(),
            ],
            'roles' => $this->roleOptions(),
            'selectedRoles' => $user->getRoleNames()->values(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];

        if ($user->isSuperAdmin() && ! in_array(User::SUPERADMIN_ROLE, $roles, true)) {
            $remaining = User::role(User::SUPERADMIN_ROLE)
                ->where('id', '!=', $user->id)
                ->count();

            if ($remaining < 1) {
                return back()->with('error', 'Minimal satu akun superadmin harus tetap ada.');
            }
        }

        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values(),
        ];

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $user->syncRoles($roles);

        $this->auditLogger->log(
            action: 'updated',
            module: 'users',
            description: "User {$user->email} diperbarui",
            auditable: $user,
            oldValues: $old,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values(),
                'password_changed' => ! empty($data['password']),
            ],
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Akun superadmin tidak dapat dihapus.');
        }

        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values(),
        ];

        $email = $user->email;
        $user->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'users',
            description: "User {$email} dihapus",
            oldValues: $old,
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function roleOptions(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
            ])
            ->all();
    }
}
