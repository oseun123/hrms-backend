<?php

namespace Database\Seeders;

use App\Models\Leave\LeaveType;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        $defaultTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'AL',
                'description' => 'Standard yearly vacation leave',
                'is_paid' => true,
                'requires_attachment' => false,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'description' => 'Leave for medical reasons',
                'is_paid' => true,
                'requires_attachment' => true,
            ],
            [
                'name' => 'Casual Leave',
                'code' => 'CL',
                'description' => 'Unplanned short-term leave',
                'is_paid' => true,
                'requires_attachment' => false,
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'ML',
                'description' => 'Leave for child birth',
                'is_paid' => true,
                'requires_attachment' => true,
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'PL',
                'description' => 'Leave for fathers after child birth',
                'is_paid' => true,
                'requires_attachment' => true,
            ],
            [
                'name' => 'Bereavement Leave',
                'code' => 'BL',
                'description' => 'Leave for the loss of a loved one',
                'is_paid' => true,
                'requires_attachment' => false,
            ],
            [
                'name' => 'Study Leave',
                'code' => 'STL',
                'description' => 'Leave for educational purposes',
                'is_paid' => false,
                'requires_attachment' => true,
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($defaultTypes as $type) {
                LeaveType::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'code' => $type['code'],
                    ],
                    array_merge($type, ['is_seeded' => true, 'is_active' => true])
                );
            }
        }
    }
}
