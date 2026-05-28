<?php

namespace Database\Seeders;

use App\Models\Preference\Preference;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PublicHolidaySeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Run the database seeder.
     * Seeds common Nigerian public holidays for all tenants
     */
    public function run(): void
    {
        $tenants = $this->tenant ? collect([$this->tenant]) : Tenant::all();

        // Define 2025 public holidays
        $holidays = [
            [
                'date' => '01-01',
                'name' => 'New Year\'s Day',
                'type' => 'National',
            ],
            [
                'date' => '04-18',
                'name' => 'Good Friday',
                'type' => 'National',
            ],
            [
                'date' => '04-21',
                'name' => 'Easter Monday',
                'type' => 'National',
            ],
            [
                'date' => '05-01',
                'name' => 'Workers\' Day',
                'type' => 'National',
            ],
            [
                'date' => '06-12',
                'name' => 'Democracy Day',
                'type' => 'National',
            ],
            [
                'date' => '03-31',
                'name' => 'Eid al-Fitr',
                'type' => 'Religious',
            ],
            [
                'date' => '06-07',
                'name' => 'Eid al-Adha',
                'type' => 'Religious',
            ],
            [
                'date' => '10-01',
                'name' => 'Independence Day',
                'type' => 'National',
            ],
            [
                'date' => '12-25',
                'name' => 'Christmas Day',
                'type' => 'National',
            ],
            [
                'date' => '12-26',
                'name' => 'Boxing Day',
                'type' => 'National',
            ],
        ];

        foreach ($tenants as $tenant) {
            // Clear existing holidays to avoid duplicates and remove old format
            Preference::where('tenant_id', $tenant->id)
                ->where('category', 'holidays')
                ->delete();

            foreach ($holidays as $holiday) {
                // Create a unique key for each holiday (MM_DD)
                $key = 'holiday_' . str_replace('-', '_', $holiday['date']);

                Preference::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'user_id' => null,
                        'category' => 'holidays',
                        'key' => $key,
                    ],
                    [
                        'value' => [
                            'date' => $holiday['date'],
                            'name' => $holiday['name'],
                            'type' => $holiday['type'],
                        ],
                    ]
                );
            }
        }

        $this->command?->info('Public holidays seeded successfully for all tenants.');
    }
}
