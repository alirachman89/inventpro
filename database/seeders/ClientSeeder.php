<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'code' => 'CLI-A',
                'name' => 'Client A',
                'type' => 'project_site',
                'contact_person' => 'PIC Client A',
                'phone' => '0811-1000-2000',
                'address' => 'Proyek Client A — Site Tower 1',
                'notes' => 'Contoh: Scaffold dipinjam Karyawan A untuk dipakai di Client A',
            ],
            [
                'code' => 'CLI-B',
                'name' => 'Client B — Mall Renovation',
                'type' => 'project_site',
                'contact_person' => 'Site Manager B',
                'phone' => '0811-3000-4000',
                'address' => 'Mall Renovation Site B',
            ],
            [
                'code' => 'CLI-INT',
                'name' => 'Unit Internal Workshop',
                'type' => 'internal_unit',
                'contact_person' => 'Kepala Workshop',
                'address' => 'Gedung Workshop InventPro',
            ],
            [
                'code' => 'CLI-CORP',
                'name' => 'PT Mitra Konstruksi Nusantara',
                'type' => 'company',
                'contact_person' => 'Procurement MKN',
                'email' => 'proc@mitrakonstruksi.example',
                'phone' => '021-8888008',
                'tax_id' => '01.234.567.8-901.000',
            ],
            [
                'code' => 'CLI-PERS',
                'name' => 'Bapak Rudi (kontraktor perorangan)',
                'type' => 'individual',
                'phone' => '0812-9000-1000',
            ],
        ];

        foreach ($clients as $client) {
            Client::query()->updateOrCreate(
                ['code' => $client['code']],
                [...$client, 'is_active' => true],
            );
        }
    }
}
