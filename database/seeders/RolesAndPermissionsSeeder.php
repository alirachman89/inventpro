<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'audit_logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolePermissions = [
            'superadmin' => $permissions,
            'admin' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',
                'audit_logs.view',
            ],
            'purchasing' => [
                'dashboard.view',
            ],
            'warehouse' => [
                'dashboard.view',
            ],
            'approver' => [
                'dashboard.view',
            ],
            'viewer' => [
                'dashboard.view',
                'audit_logs.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}
