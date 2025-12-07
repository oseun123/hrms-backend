<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;
use App\Models\Department;
use App\Models\Level;
use App\Models\Grade;
use App\Models\User;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $adminUser = User::where('email', 'admin@hrms.local')->first();

        $devDept = Department::where('code', 'IT-DEV')->first();
        $hrDept = Department::where('code', 'HR')->first();
        $midLevel = Level::where('code', 'MID')->first();
        $seniorLevel = Level::where('code', 'SR')->first();
        $grade2 = Grade::where('code', 'G2')->first();
        $grade3 = Grade::where('code', 'G3')->first();

        Position::create([
            'tenant_id' => $tenantId,
            'department_id' => $devDept->id,
            'level_id' => $midLevel->id,
            'grade_id' => $grade2->id,
            'code' => 'DEV-001',
            'title' => 'Software Developer',
            'description' => 'Full Stack Developer',
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        Position::create([
            'tenant_id' => $tenantId,
            'department_id' => $hrDept->id,
            'level_id' => $seniorLevel->id,
            'grade_id' => $grade3->id,
            'code' => 'HR-001',
            'title' => 'HR Manager',
            'description' => 'Human Resources Manager',
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        $this->command->info('Positions seeded successfully!');
    }
}
