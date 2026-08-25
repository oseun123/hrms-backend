<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Attendance\AttendanceWorkScheduleSetting;
use App\Models\Attendance\AttendanceApprovalSetting;
use Illuminate\Database\Seeder;

class AttendanceDefaultsSeeder extends Seeder
{
    protected $tenant;

    public function __construct(Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = ($this->tenant && $this->tenant->id) ? $this->tenant->id : 1;

        // Create default work schedule setting
        AttendanceWorkScheduleSetting::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'arrival_grace' => 15,
                'departure_grace' => 15,
                'work_days' => [
                    'monday' => ['morning' => ['start' => '09:00:00', 'end' => '17:00:00']],
                    'tuesday' => ['morning' => ['start' => '09:00:00', 'end' => '17:00:00']],
                    'wednesday' => ['morning' => ['start' => '09:00:00', 'end' => '17:00:00']],
                    'thursday' => ['morning' => ['start' => '09:00:00', 'end' => '17:00:00']],
                    'friday' => ['morning' => ['start' => '09:00:00', 'end' => '17:00:00']],
                ],
                'overtime_days' => [],
                'is_shift' => false,
                'enable_payroll' => false,
                'enable_geofence' => true,
                'geofence_radius' => 100,
                'enable_webcam' => false,
                'time_zone' => 'Africa/Lagos'
            ]
        );

        // Create default approval settings
        AttendanceApprovalSetting::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'first_approver' => 'primary-line-manager',
                'second_approver' => null,
                'third_approver' => null,
                'lateness_fee_approval' => 'primary-line-manager',
                'absenteeism_fee_approval' => 'primary-line-manager',
                'overtime_fee_approval' => 'primary-line-manager',
                'overtime_time_fee' => false
            ]
        );
    }
}
