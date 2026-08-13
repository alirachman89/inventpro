<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::setMany([
            'company_name' => 'InventPro Demo',
            'company_address' => 'Jl. Contoh InventPro No. 1, Jakarta',
            'company_phone' => '021-0000000',
            'timezone' => 'Asia/Jakarta',
            'prefix_po' => 'PO',
            'prefix_gr' => 'GR',
            'prefix_opname' => 'OPN',
            'prefix_borrow' => 'BRW',
            'prefix_movement' => 'MOV',
        ], 'company');
    }
}
