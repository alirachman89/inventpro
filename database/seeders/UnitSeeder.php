<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'pcs', 'name' => 'Pieces', 'symbol' => 'pcs', 'type' => 'count', 'sort_order' => 10],
            ['code' => 'unit', 'name' => 'Unit', 'symbol' => 'unit', 'type' => 'count', 'sort_order' => 20],
            ['code' => 'box', 'name' => 'Box', 'symbol' => 'box', 'type' => 'count', 'sort_order' => 30],
            ['code' => 'pack', 'name' => 'Pack', 'symbol' => 'pack', 'type' => 'count', 'sort_order' => 40],
            ['code' => 'set', 'name' => 'Set', 'symbol' => 'set', 'type' => 'count', 'sort_order' => 50],
            ['code' => 'pasang', 'name' => 'Pasang', 'symbol' => 'psg', 'type' => 'count', 'sort_order' => 60],
            ['code' => 'kg', 'name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight', 'sort_order' => 70],
            ['code' => 'g', 'name' => 'Gram', 'symbol' => 'g', 'type' => 'weight', 'sort_order' => 80],
            ['code' => 'ltr', 'name' => 'Liter', 'symbol' => 'L', 'type' => 'volume', 'sort_order' => 90],
            ['code' => 'ml', 'name' => 'Mililiter', 'symbol' => 'ml', 'type' => 'volume', 'sort_order' => 100],
            ['code' => 'm', 'name' => 'Meter', 'symbol' => 'm', 'type' => 'length', 'sort_order' => 110],
            ['code' => 'cm', 'name' => 'Centimeter', 'symbol' => 'cm', 'type' => 'length', 'sort_order' => 120],
            ['code' => 'roll', 'name' => 'Roll', 'symbol' => 'roll', 'type' => 'other', 'sort_order' => 130],
        ];

        foreach ($units as $unit) {
            Unit::query()->updateOrCreate(
                ['code' => $unit['code']],
                [...$unit, 'is_active' => true],
            );
        }
    }
}
