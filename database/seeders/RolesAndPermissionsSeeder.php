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
            'approvals.manage',
            'approvals.act',
            'approval_demos.manage',
            'units.view',
            'units.create',
            'units.update',
            'units.delete',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'locations.view',
            'locations.create',
            'locations.update',
            'locations.delete',
            'racks.view',
            'racks.create',
            'racks.update',
            'racks.delete',
            'settings.view',
            'settings.update',
            'items.view',
            'items.create',
            'items.update',
            'items.delete',
            'item_stocks.view',
            'item_stocks.adjust',
            'asset_units.view',
            'asset_units.create',
            'asset_units.update',
            'asset_units.set_status',
            'stock_ledgers.view',
            'vendors.view',
            'vendors.create',
            'vendors.update',
            'vendors.delete',
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',
            'purchases.view',
            'purchases.create',
            'purchases.update',
            'purchases.delete',
            'purchases.submit',
            'purchases.export',
            'goods_receipts.view',
            'goods_receipts.create',
            'stock_movements.view',
            'stock_movements.create',
            'stock_opnames.view',
            'stock_opnames.create',
            'stock_opnames.update',
            'stock_opnames.submit',
            'stock_opnames.post',
            'stock_opnames.delete',
            'borrows.view',
            'borrows.create',
            'borrows.update',
            'borrows.submit',
            'borrows.checkout',
            'borrows.return',
            'borrows.delete',
            'reports.view',
            'reports.export',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $masterData = [
            'units.view', 'units.create', 'units.update', 'units.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'locations.view', 'locations.create', 'locations.update', 'locations.delete',
            'racks.view', 'racks.create', 'racks.update', 'racks.delete',
            'settings.view', 'settings.update',
            'vendors.view', 'vendors.create', 'vendors.update', 'vendors.delete',
            'clients.view', 'clients.create', 'clients.update', 'clients.delete',
        ];

        $stockOps = [
            'items.view', 'items.create', 'items.update', 'items.delete',
            'item_stocks.view', 'item_stocks.adjust',
            'asset_units.view', 'asset_units.create', 'asset_units.update', 'asset_units.set_status',
            'stock_ledgers.view',
            'stock_movements.view', 'stock_movements.create',
            'stock_opnames.view', 'stock_opnames.create', 'stock_opnames.update',
            'stock_opnames.submit', 'stock_opnames.post', 'stock_opnames.delete',
            'borrows.view', 'borrows.create', 'borrows.update', 'borrows.submit',
            'borrows.checkout', 'borrows.return', 'borrows.delete',
        ];

        $stockView = [
            'items.view', 'item_stocks.view', 'asset_units.view', 'stock_ledgers.view',
            'stock_movements.view',
            'stock_opnames.view',
            'borrows.view',
        ];

        $masterView = [
            'units.view', 'categories.view', 'locations.view', 'racks.view',
            'vendors.view', 'clients.view',
        ];

        $vendorOps = [
            'vendors.view', 'vendors.create', 'vendors.update', 'vendors.delete',
        ];

        $purchaseOps = [
            'purchases.view', 'purchases.create', 'purchases.update', 'purchases.delete', 'purchases.submit',
            'purchases.export',
            'goods_receipts.view',
        ];

        $grOps = [
            'purchases.view',
            'purchases.export',
            'goods_receipts.view', 'goods_receipts.create',
        ];

        $rolePermissions = [
            'superadmin' => $permissions,
            'admin' => array_values(array_unique(array_merge([
                'dashboard.view',
                'users.view', 'users.create', 'users.update', 'users.delete',
                'roles.view', 'roles.create', 'roles.update', 'roles.delete',
                'audit_logs.view',
                'approvals.manage', 'approvals.act',
                'approval_demos.manage',
            ], $masterData, $stockOps, $purchaseOps, ['goods_receipts.create', 'reports.view', 'reports.export']))),
            'purchasing' => array_merge([
                'dashboard.view',
                'approval_demos.manage',
                'clients.view',
                'reports.view',
                'reports.export',
            ], $masterView, $stockView, $vendorOps, $purchaseOps),
            'warehouse' => array_merge([
                'dashboard.view',
                'approval_demos.manage',
                'clients.view',
                'vendors.view',
                'reports.view',
                'reports.export',
            ], [
                'units.view', 'categories.view', 'locations.view', 'racks.view',
            ], $stockOps, $grOps),
            'approver' => [
                'dashboard.view',
                'approvals.act',
                'purchases.view',
                'purchases.export',
                'stock_opnames.view',
                'borrows.view',
                'reports.view',
            ],
            'viewer' => array_merge([
                'dashboard.view',
                'audit_logs.view',
                'purchases.view',
                'purchases.export',
                'goods_receipts.view',
                'reports.view',
            ], $masterView, $stockView),
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(array_values(array_unique($perms)));
        }
    }
}
