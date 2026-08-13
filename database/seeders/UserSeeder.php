<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@inventpro.local',
                'role' => 'superadmin',
            ],
            [
                'name' => 'Admin InventPro',
                'email' => 'admin@inventpro.local',
                'role' => 'admin',
            ],
            [
                'name' => 'Purchasing User',
                'email' => 'purchasing@inventpro.local',
                'role' => 'purchasing',
            ],
            [
                'name' => 'Warehouse User',
                'email' => 'warehouse@inventpro.local',
                'role' => 'warehouse',
            ],
            [
                'name' => 'Approver User',
                'email' => 'approver@inventpro.local',
                'role' => 'approver',
            ],
            [
                'name' => 'Viewer User',
                'email' => 'viewer@inventpro.local',
                'role' => 'viewer',
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'Password123!',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
