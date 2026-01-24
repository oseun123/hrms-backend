<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DepartmentSeeder::class,
            LevelSeeder::class,
            GradeSeeder::class,
            PositionSeeder::class,
            EmployeeSeeder::class,
            DocumentTypeSeeder::class,
            SkillSeeder::class,
            DefaultSecurityPoliciesSeeder::class,
            DefaultPreferencesSeeder::class,
            PublicHolidaySeeder::class,
            EmployeeNumberFormatSeeder::class,
            PermissionSeeder::class,
            LeaveTypeSeeder::class,
            LeaveWorkflowSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('====================================');
        $this->command->info('Database seeded successfully!');
        $this->command->info('====================================');
        $this->command->info('Admin: admin@hrms.local / password');
        $this->command->info('Employee: john.doe@hrms.local / password');
        $this->command->info('====================================');
    }
}
