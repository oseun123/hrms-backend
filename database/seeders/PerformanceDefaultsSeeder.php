<?php

namespace Database\Seeders;

use App\Models\Performance\Competency;
use App\Models\Performance\GoalAreaOfFocus;
use App\Models\Performance\PerformanceGoal;
use App\Models\Performance\PerformanceMeasureTarget;
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
        $tenants = ($this->tenant && $this->tenant->exists) ? collect([$this->tenant]) : Tenant::all();

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

            // Seed 7 Default Simple Goals
            $defaultGoals = [
                [
                    'area' => 'Strategic Goals',
                    'title' => 'Revenue Target Achievement',
                    'description' => 'Achieve assigned revenue and business development targets for the performance cycle.',
                    'measure_description' => 'Closed-won revenue against assigned quota',
                    'target_description' => 'Meet or exceed 100% of assigned revenue quota',
                    'uom' => '%',
                    'weightage' => 25,
                ],
                [
                    'area' => 'Operational Excellence',
                    'title' => 'On-Time Project & Milestone Delivery',
                    'description' => 'Ensure all assigned projects, deliverables, and milestones are completed within agreed deadlines.',
                    'measure_description' => 'Milestones and deliverables completed on time',
                    'target_description' => '95% on-time milestone completion rate',
                    'uom' => '%',
                    'weightage' => 20,
                ],
                [
                    'area' => 'Operational Excellence',
                    'title' => 'Quality Assurance & SLA Compliance',
                    'description' => 'Maintain high operational quality standards and adhere strictly to Service Level Agreements.',
                    'measure_description' => 'SLA adherence and operational error rate',
                    'target_description' => 'Zero critical SLA breaches and 98% uptime / compliance',
                    'uom' => '%',
                    'weightage' => 15,
                ],
                [
                    'area' => 'Customer Focus',
                    'title' => 'Customer Satisfaction (CSAT)',
                    'description' => 'Deliver high-quality service and support to maintain outstanding customer satisfaction ratings.',
                    'measure_description' => 'Average customer satisfaction feedback score',
                    'target_description' => 'Maintain an average CSAT rating of 90% or higher',
                    'uom' => '%',
                    'weightage' => 15,
                ],
                [
                    'area' => 'Customer Focus',
                    'title' => 'Client Retention & Account Health',
                    'description' => 'Foster strong customer relationships and drive proactive client engagement and renewals.',
                    'measure_description' => 'Client retention and proactive relationship reviews',
                    'target_description' => 'Achieve 90% client retention and conduct quarterly reviews',
                    'uom' => '%',
                    'weightage' => 10,
                ],
                [
                    'area' => 'Innovation & Growth',
                    'title' => 'Process Optimization & Efficiency',
                    'description' => 'Identify inefficiencies and implement innovative process improvements or automation.',
                    'measure_description' => 'Process optimization initiatives implemented',
                    'target_description' => 'Deliver at least 2 process improvement initiatives',
                    'uom' => 'Initiatives',
                    'weightage' => 10,
                ],
                [
                    'area' => 'Team Development',
                    'title' => 'Continuous Learning & Upskilling',
                    'description' => 'Expand professional capabilities through training, certifications, and internal knowledge sharing.',
                    'measure_description' => 'Professional training completed and knowledge sessions hosted',
                    'target_description' => 'Complete 40 hours of training or host 2 knowledge-share sessions',
                    'uom' => 'Hours',
                    'weightage' => 5,
                ],
            ];

            foreach ($defaultGoals as $goalData) {
                $area = GoalAreaOfFocus::where('tenant_id', $tenant->id)
                    ->where('name', $goalData['area'])
                    ->first();

                if ($area) {
                    $goal = PerformanceGoal::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'title' => $goalData['title'],
                        ],
                        [
                            'area_of_focus_id' => $area->id,
                            'description' => $goalData['description'],
                            'goal_type' => 'simple',
                            'is_active' => true,
                        ]
                    );

                    PerformanceMeasureTarget::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'measurable_type' => PerformanceGoal::class,
                            'measurable_id' => $goal->id,
                        ],
                        [
                            'measure_description' => $goalData['measure_description'],
                            'target_description' => $goalData['target_description'],
                            'uom' => $goalData['uom'],
                            'weightage' => $goalData['weightage'],
                        ]
                    );
                }
            }
        }
    }
}
