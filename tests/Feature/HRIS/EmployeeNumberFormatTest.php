<?php

namespace Tests\Feature\HRIS;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeNumberFormatTest extends TestCase
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

    public function test_validation_passes_when_include_month_is_false_and_month_format_is_null()
    {
        $payload = [
            "prefix" => "Tet",
            "separator" => "-",
            "include_year" => false,
            "year_format" => "YYYY",
            "include_month" => false,
            "month_format" => null,
            "sequence_length" => 4,
            "reset_sequence" => "yearly",
            "format_name" => "Default Format",
            "is_active" => true
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/hris/employee-number-format', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    public function test_preview_validation_passes_when_include_month_is_false_and_month_format_is_null()
    {
        $payload = [
            "prefix" => "Tet",
            "separator" => "-",
            "include_year" => false,
            "year_format" => "YYYY",
            "include_month" => false,
            "month_format" => null,
            "sequence_length" => 4,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/hris/employee-number-format/preview', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }
}
