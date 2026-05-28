<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@hrms.local');
        $password = env('SUPER_ADMIN_PASSWORD', 'password');

        SuperAdmin::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'System Super Admin',
                'password' => Hash::make($password),
            ]
        );

        $this->command?->info('Super admin seeded successfully!');
    }
}
