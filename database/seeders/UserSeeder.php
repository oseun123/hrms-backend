<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@hrms.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => 1, // Default tenant
        ]);

        // Create employee user
        User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@hrms.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => 1, // Default tenant
        ]);

        $this->command->info('Users seeded successfully!');
    }
}
