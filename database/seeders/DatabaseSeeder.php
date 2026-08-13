<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            ApprovalWorkflowSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            LocationSeeder::class,
            SettingSeeder::class,
            VendorSeeder::class,
            ClientSeeder::class,
            ItemSeeder::class,
            PurchaseOrderSeeder::class,
            StockMovementSeeder::class,
            StockOpnameSeeder::class,
        ]);
    }
}
