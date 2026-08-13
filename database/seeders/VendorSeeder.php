<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            [
                'code' => 'VND-SCAF',
                'name' => 'PT Scaffoldindo Utama',
                'contact_person' => 'Budi Santoso',
                'email' => 'sales@scaffoldindo.example',
                'phone' => '021-1111001',
                'address' => 'Kawasan Industri Scaffold, Bekasi',
                'tax_id' => '10.111.222.3-444.000',
            ],
            [
                'code' => 'VND-FAST',
                'name' => 'CV Fastener Jaya',
                'contact_person' => 'Siti Aminah',
                'email' => 'order@fastenerjaya.example',
                'phone' => '021-2222002',
                'address' => 'Jl. Baja No. 12, Tangerang',
            ],
            [
                'code' => 'VND-TOOL',
                'name' => 'Toko Perkakas Mandiri',
                'contact_person' => 'Andi Wijaya',
                'phone' => '0812-3000-4000',
                'address' => 'Jl. Raya Industri 45, Jakarta',
            ],
            [
                'code' => 'VND-PPE',
                'name' => 'PT Safety Gear Indonesia',
                'contact_person' => 'Rina Safitri',
                'email' => 'cs@safetygear.example',
                'phone' => '021-5555005',
            ],
            [
                'code' => 'VND-GEN',
                'name' => 'UD Sumber Teknik',
                'contact_person' => 'Hendra',
                'phone' => '0813-7000-8000',
                'notes' => 'Vendor umum consumable',
            ],
        ];

        foreach ($vendors as $vendor) {
            Vendor::query()->updateOrCreate(
                ['code' => $vendor['code']],
                [...$vendor, 'is_active' => true],
            );
        }
    }
}
