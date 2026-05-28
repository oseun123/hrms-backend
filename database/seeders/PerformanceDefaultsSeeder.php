<?php

namespace Database\Seeders;

use App\Models\Performance\Competency;
use App\Models\Performance\GoalAreaOfFocus;
use App\Models\Performance\PerformanceSetting;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PerformanceDefaultsSeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = $this->tenant ? collect([$this->tenant]) : Tenant::all();

        foreach ($tenants as $tenant) {
            // Seed default performance settings
            PerformanceSetting::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'cycle_type' => 'annual',
                    'reviewer_levels' => 2,
                    'final_score_level' => 2,
                    'results_weight' => 70.00,
                    'competency_weight' => 30.00,
                    'goal_structure' => 'simple',
                    'enforce_submit_back' => false,
                ]
            );

            // Seed default areas of focus
            $areasOfFocus = [
                ['name' => 'Strategic Goals', 'description' => 'Long-term strategic objectives aligned with company vision'],
                ['name' => 'Operational Excellence', 'description' => 'Day-to-day operational targets and efficiency improvements'],
                ['name' => 'Customer Focus', 'description' => 'Customer satisfaction and service quality goals'],
                ['name' => 'Innovation & Growth', 'description' => 'New initiatives, process improvements, and revenue growth'],
                ['name' => 'Team Development', 'description' => 'Team building, mentoring, and talent development goals'],
            ];

            foreach ($areasOfFocus as $area) {
                GoalAreaOfFocus::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $area['name'],
                    ],
                    [
                        'description' => $area['description'],
                        'is_seeded' => true,
                        'is_active' => true,
                    ]
                );
            }

            // Seed default competencies with weightages totaling 100%
            $competencies = [
                ['name' => 'Communication', 'description' => 'Ability to convey information clearly and effectively', 'weightage' => 15],
                ['name' => 'Teamwork', 'description' => 'Collaboration and working effectively with others', 'weightage' => 15],
                ['name' => 'Problem Solving', 'description' => 'Analytical thinking and finding solutions', 'weightage' => 15],
                ['name' => 'Leadership', 'description' => 'Guiding, motivating, and inspiring others', 'weightage' => 15],
                ['name' => 'Adaptability', 'description' => 'Flexibility and ability to handle change', 'weightage' => 10],
                ['name' => 'Time Management', 'description' => 'Prioritization and meeting deadlines', 'weightage' => 10],
                ['name' => 'Technical Skills', 'description' => 'Job-specific knowledge and expertise', 'weightage' => 20],
            ];

            foreach ($competencies as $competency) {
                Competency::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $competency['name'],
                    ],
                    [
                        'description' => $competency['description'],
                        'weightage' => $competency['weightage'],
                        'is_seeded' => true,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
