<?php

namespace Tests\Feature\HRIS;

use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Grade;
use App\Models\Level;
use App\Models\Position;
use App\Models\State;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@hrms.local',
        ]);

        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    public function test_can_get_employees_on_probation()
    {
        // Create necessary relations
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $grade = Grade::factory()->create();
        $level = Level::factory()->create();
        $country = Country::factory()->create();
        $state = State::factory()->create(['country_id' => $country->id]);
        $city = City::factory()->create(['state_id' => $state->id]);

        // Create employee on probation
        $employee = Employee::factory()->create([
            'probation_end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ]);

        EmploymentDetail::factory()->create([
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'grade_id' => $grade->id,
            'level_id' => $level->id,
            'work_email' => 'test@work.com',
            'employment_type' => 'full-time',
            'employment_status' => 'active',
            'hire_date' => Carbon::now()->subMonths(1),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hris/dashboard/employees/on-probation');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'probation_end_date',
                        'days_remaining',
                        'is_today',
                    ]
                ]
            ]);
    }

    public function test_can_get_birthdays_this_month()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $grade = Grade::factory()->create();
        $level = Level::factory()->create();
        // Create employee with birthday this month
        $employee = Employee::factory()->create([
            'date_of_birth' => Carbon::now()->subYears(25)->format('Y-m-d'), // 25th birthday today/this month
        ]);
        EmploymentDetail::factory()->create([
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'grade_id' => $grade->id,
            'level_id' => $level->id,
            'work_email' => 'test2@work.com',
            'employment_type' => 'full-time',
            'employment_status' => 'active',
            'hire_date' => Carbon::now()->subMonths(1),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hris/dashboard/employees/birthdays-this-month');

        $response->assertStatus(200)
            ->assertJsonFragment(['age' => 25])
            ->assertJsonFragment(['is_today' => true]);
    }

    public function test_can_get_anniversaries_this_month()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $grade = Grade::factory()->create();
        $level = Level::factory()->create();
        // Create employee with work anniversary this month
        $hireDate = Carbon::now()->subYears(2)->format('Y-m-d'); // 2 years ago today/this month
        $employee = Employee::factory()->create();

        EmploymentDetail::factory()->create([
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'grade_id' => $grade->id,
            'level_id' => $level->id,
            'work_email' => 'test3@work.com',
            'employment_type' => 'full-time',
            'employment_status' => 'active',
            'hire_date' => $hireDate,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hris/dashboard/employees/anniversaries-this-month');

        $response->assertStatus(200)
            ->assertJsonFragment(['years_of_service' => 2])
            ->assertJsonFragment(['is_today' => true]);
    }
}
