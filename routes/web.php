<?php

use App\Http\Controllers\Admin\ApprovalWorkflowController;
use App\Http\Controllers\Admin\AssetUnitController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\GoodsReceiptController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\RackController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\StockOpnameController;
use App\Http\Controllers\Admin\BorrowController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\ApprovalDemoController;
use App\Http\Controllers\ApprovalRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/search', SearchController::class)->name('search');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/approvals', [ApprovalRequestController::class, 'index'])->name('approvals.index');
    Route::get('/approvals/{approval}', [ApprovalRequestController::class, 'show'])->name('approvals.show');

    Route::middleware('permission:approvals.act')->group(function () {
        Route::post('/approvals/{approval}/approve', [ApprovalRequestController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{approval}/reject', [ApprovalRequestController::class, 'reject'])->name('approvals.reject');
    });

    Route::middleware('permission:approval_demos.manage')->group(function () {
        Route::get('/approval-demos', [ApprovalDemoController::class, 'index'])->name('approval-demos.index');
        Route::post('/approval-demos', [ApprovalDemoController::class, 'store'])->name('approval-demos.store');
        Route::post('/approval-demos/{approvalDemo}/submit', [ApprovalDemoController::class, 'submit'])->name('approval-demos.submit');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
        });
        Route::middleware('permission:users.create')->group(function () {
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
        });
        Route::middleware('permission:users.update')->group(function () {
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        });
        Route::middleware('permission:users.delete')->group(function () {
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

        Route::middleware('permission:roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        });
        Route::middleware('permission:roles.create')->group(function () {
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        });
        Route::middleware('permission:roles.update')->group(function () {
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });
        Route::middleware('permission:roles.delete')->group(function () {
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });

        Route::middleware('permission:audit_logs.view')->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
        });

        Route::middleware('permission:approvals.manage')->group(function () {
            Route::get('/approval-workflows', [ApprovalWorkflowController::class, 'index'])->name('approval-workflows.index');
            Route::get('/approval-workflows/{workflow}/edit', [ApprovalWorkflowController::class, 'edit'])->name('approval-workflows.edit');
            Route::put('/approval-workflows/{workflow}', [ApprovalWorkflowController::class, 'update'])->name('approval-workflows.update');
        });

        Route::middleware('permission:units.view')->group(function () {
            Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        });
        Route::middleware('permission:units.create')->group(function () {
            Route::get('/units/create', [UnitController::class, 'create'])->name('units.create');
            Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        });
        Route::middleware('permission:units.update')->group(function () {
            Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
            Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
        });
        Route::middleware('permission:units.delete')->group(function () {
            Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
        });

        Route::middleware('permission:categories.view')->group(function () {
            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        });
        Route::middleware('permission:categories.create')->group(function () {
            Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        });
        Route::middleware('permission:categories.update')->group(function () {
            Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
            Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        });
        Route::middleware('permission:categories.delete')->group(function () {
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        });

        Route::middleware('permission:locations.view')->group(function () {
            Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        });
        Route::middleware('permission:locations.create')->group(function () {
            Route::get('/locations/create', [LocationController::class, 'create'])->name('locations.create');
            Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        });
        Route::middleware('permission:locations.update')->group(function () {
            Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
            Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        });
        Route::middleware('permission:locations.view')->group(function () {
            Route::get('/locations/{location}', [LocationController::class, 'show'])->name('locations.show');
        });
        Route::middleware('permission:locations.delete')->group(function () {
            Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
        });

        Route::middleware('permission:racks.create')->group(function () {
            Route::get('/locations/{location}/racks/create', [RackController::class, 'create'])->name('racks.create');
            Route::post('/locations/{location}/racks', [RackController::class, 'store'])->name('racks.store');
        });
        Route::middleware('permission:racks.update')->group(function () {
            Route::get('/racks/{rack}/edit', [RackController::class, 'edit'])->name('racks.edit');
            Route::put('/racks/{rack}', [RackController::class, 'update'])->name('racks.update');
        });
        Route::middleware('permission:racks.delete')->group(function () {
            Route::delete('/racks/{rack}', [RackController::class, 'destroy'])->name('racks.destroy');
        });

        Route::middleware('permission:settings.view')->group(function () {
            Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        });
        Route::middleware('permission:settings.update')->group(function () {
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        });

        Route::middleware('permission:items.view')->group(function () {
            Route::get('/items', [ItemController::class, 'index'])->name('items.index');
        });
        Route::middleware('permission:items.create')->group(function () {
            Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
            Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        });
        Route::middleware('permission:items.update')->group(function () {
            Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
            Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
        });
        Route::middleware('permission:item_stocks.adjust')->group(function () {
            Route::post('/items/{item}/adjust-stock', [ItemController::class, 'adjustStock'])->name('items.adjust-stock');
        });
        Route::middleware('permission:items.view')->group(function () {
            Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
        });
        Route::middleware('permission:items.delete')->group(function () {
            Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
        });

        Route::middleware('permission:asset_units.create')->group(function () {
            Route::get('/items/{item}/asset-units/create', [AssetUnitController::class, 'create'])->name('asset-units.create');
            Route::post('/items/{item}/asset-units', [AssetUnitController::class, 'store'])->name('asset-units.store');
        });
        Route::middleware('permission:asset_units.view')->group(function () {
            Route::get('/asset-units/{assetUnit}', [AssetUnitController::class, 'show'])->name('asset-units.show');
        });
        Route::middleware('permission:asset_units.set_status')->group(function () {
            Route::post('/asset-units/{assetUnit}/status', [AssetUnitController::class, 'changeStatus'])->name('asset-units.change-status');
        });

        Route::middleware('permission:vendors.view')->group(function () {
            Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
        });
        Route::middleware('permission:vendors.create')->group(function () {
            Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
            Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
        });
        Route::middleware('permission:vendors.update')->group(function () {
            Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
            Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        });
        Route::middleware('permission:vendors.view')->group(function () {
            Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
        });
        Route::middleware('permission:vendors.delete')->group(function () {
            Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
        });

        Route::middleware('permission:clients.view')->group(function () {
            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        });
        Route::middleware('permission:clients.create')->group(function () {
            Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
            Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        });
        Route::middleware('permission:clients.update')->group(function () {
            Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
            Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        });
        Route::middleware('permission:clients.view')->group(function () {
            Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        });
        Route::middleware('permission:clients.delete')->group(function () {
            Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
        });

        Route::middleware('permission:purchases.view')->group(function () {
            Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        });
        Route::middleware('permission:purchases.create')->group(function () {
            Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
            Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        });
        Route::middleware('permission:purchases.update')->group(function () {
            Route::get('/purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
            Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        });
        Route::middleware('permission:purchases.submit')->group(function () {
            Route::post('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
        });
        Route::middleware('permission:purchases.view')->group(function () {
            Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        });
        Route::middleware('permission:purchases.export')->group(function () {
            Route::get('/purchase-orders/{purchaseOrder}/export/pdf', [PurchaseOrderController::class, 'exportPdf'])->name('purchase-orders.export-pdf');
            Route::get('/purchase-orders/{purchaseOrder}/export/excel', [PurchaseOrderController::class, 'exportExcel'])->name('purchase-orders.export-excel');
        });
        Route::middleware('permission:purchases.delete')->group(function () {
            Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
        });

        Route::middleware('permission:goods_receipts.create')->group(function () {
            Route::get('/purchase-orders/{purchaseOrder}/goods-receipts/create', [GoodsReceiptController::class, 'create'])->name('goods-receipts.create');
            Route::post('/purchase-orders/{purchaseOrder}/goods-receipts', [GoodsReceiptController::class, 'store'])->name('goods-receipts.store');
        });

        Route::middleware('permission:stock_movements.view')->group(function () {
            Route::get('/stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
        });
        Route::middleware('permission:stock_movements.create')->group(function () {
            Route::get('/stock-movements/create', [StockMovementController::class, 'create'])->name('stock-movements.create');
            Route::post('/stock-movements', [StockMovementController::class, 'store'])->name('stock-movements.store');
        });
        Route::middleware('permission:stock_movements.view')->group(function () {
            Route::get('/stock-movements/{stockMovement}', [StockMovementController::class, 'show'])->name('stock-movements.show');
        });

        Route::middleware('permission:stock_opnames.view')->group(function () {
            Route::get('/stock-opnames', [StockOpnameController::class, 'index'])->name('stock-opnames.index');
        });
        Route::middleware('permission:stock_opnames.create')->group(function () {
            Route::get('/stock-opnames/create', [StockOpnameController::class, 'create'])->name('stock-opnames.create');
            Route::post('/stock-opnames', [StockOpnameController::class, 'store'])->name('stock-opnames.store');
        });
        Route::middleware('permission:stock_opnames.update')->group(function () {
            Route::put('/stock-opnames/{stockOpname}/counts', [StockOpnameController::class, 'updateCounts'])->name('stock-opnames.update-counts');
            Route::post('/stock-opnames/{stockOpname}/refresh', [StockOpnameController::class, 'refresh'])->name('stock-opnames.refresh');
        });
        Route::middleware('permission:stock_opnames.submit')->group(function () {
            Route::post('/stock-opnames/{stockOpname}/submit', [StockOpnameController::class, 'submit'])->name('stock-opnames.submit');
        });
        Route::middleware('permission:stock_opnames.post')->group(function () {
            Route::post('/stock-opnames/{stockOpname}/post', [StockOpnameController::class, 'post'])->name('stock-opnames.post');
        });
        Route::middleware('permission:stock_opnames.view')->group(function () {
            Route::get('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'show'])->name('stock-opnames.show');
        });
        Route::middleware('permission:stock_opnames.delete')->group(function () {
            Route::delete('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'destroy'])->name('stock-opnames.destroy');
        });

        Route::middleware('permission:borrows.view')->group(function () {
            Route::get('/borrows', [BorrowController::class, 'index'])->name('borrows.index');
        });
        Route::middleware('permission:borrows.create')->group(function () {
            Route::get('/borrows/create', [BorrowController::class, 'create'])->name('borrows.create');
            Route::post('/borrows', [BorrowController::class, 'store'])->name('borrows.store');
        });
        Route::middleware('permission:borrows.update')->group(function () {
            Route::get('/borrows/{borrow}/edit', [BorrowController::class, 'edit'])->name('borrows.edit');
            Route::put('/borrows/{borrow}', [BorrowController::class, 'update'])->name('borrows.update');
        });
        Route::middleware('permission:borrows.submit')->group(function () {
            Route::post('/borrows/{borrow}/submit', [BorrowController::class, 'submit'])->name('borrows.submit');
        });
        Route::middleware('permission:borrows.checkout')->group(function () {
            Route::post('/borrows/{borrow}/checkout', [BorrowController::class, 'checkout'])->name('borrows.checkout');
        });
        Route::middleware('permission:borrows.return')->group(function () {
            Route::post('/borrows/{borrow}/return', [BorrowController::class, 'returnItems'])->name('borrows.return');
        });
        Route::middleware('permission:borrows.view')->group(function () {
            Route::get('/borrows/{borrow}', [BorrowController::class, 'show'])->name('borrows.show');
        });
        Route::middleware('permission:borrows.delete')->group(function () {
            Route::delete('/borrows/{borrow}', [BorrowController::class, 'destroy'])->name('borrows.destroy');
        });

        Route::middleware('permission:reports.view')->group(function () {
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        });
        Route::middleware('permission:reports.export')->group(function () {
            Route::get('/reports/{report}/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
            Route::get('/reports/{report}/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        });
    });
});

require __DIR__.'/auth.php';
