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
            SuperAdminSeeder::class,
            DepartmentSeeder::class,
            BranchSeeder::class,
            LevelSeeder::class,
            GradeSeeder::class,
            PositionSeeder::class,
            // EmployeeSeeder::class,
            DocumentTypeSeeder::class,
            SkillSeeder::class,
            DefaultSecurityPoliciesSeeder::class,
            DefaultPreferencesSeeder::class,
            ProfileApprovalSettingsSeeder::class,
            PublicHolidaySeeder::class,
            EmployeeNumberFormatSeeder::class,
            PermissionSeeder::class,
            LeaveTypeSeeder::class,
            LeaveWorkflowSeeder::class,
            PayrollSeeder::class,
            RequestModuleSeeder::class,
            AttendanceDefaultsSeeder::class,
        ]);

        $this->command?->info('');
        $this->command?->info('====================================');
        $this->command?->info('Database seeded successfully!');
        $this->command?->info('====================================');
        $this->command?->info('Super Admin: superadmin@hrms.local / password');
        $this->command?->info('====================================');
    }
}
