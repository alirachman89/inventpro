<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'FASTENER', 'name' => 'Fastener', 'sort_order' => 10],
            ['code' => 'TOOL', 'name' => 'Perkakas', 'sort_order' => 20],
            ['code' => 'SCAFFOLD', 'name' => 'Scaffolding', 'sort_order' => 30],
            ['code' => 'PPE', 'name' => 'Alat Pelindung Diri', 'sort_order' => 40],
            ['code' => 'CONSUMABLE', 'name' => 'Habis Pakai', 'sort_order' => 50],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['code' => $category['code']],
                [...$category, 'is_active' => true],
            );
        }
    }
}
