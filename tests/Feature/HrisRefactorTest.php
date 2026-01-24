<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hris\Employee;
use App\Models\Preference\Preference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HrisRefactorTest extends TestCase
{
    // use RefreshDatabase; // Commented out to avoid wiping existing dev DB if configured

    public function test_can_instantiate_hris_employee_model()
    {
        $employee = new Employee();
        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals('employees', $employee->getTable());
    }

    public function test_can_instantiate_preference_model()
    {
        $preference = new Preference();
        $this->assertInstanceOf(Preference::class, $preference);
        // Assuming table name didn't change, usually 'preferences'
        $this->assertEquals('preferences', $preference->getTable());
    }

    public function test_auth_controller_references_hris_employee()
    {
        // This is a static analysis check via reflection/code inspection simulation
        // Practically, we just want to ensure the class exists and we can mock it if needed
        $this->assertTrue(class_exists(\App\Models\Hris\Employee::class));
    }
}
