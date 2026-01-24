<?php

namespace Tests\Feature\HRIS;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MotivationalQuoteTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_get_daily_quote()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hris/dashboard/daily-quote');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'quote',
                    'author',
                ]
            ]);
    }

    public function test_daily_quote_changes_with_date()
    {
        // Mock current time to 1st of the month
        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hris/dashboard/daily-quote');
        $quote1 = $response1->json('data.quote');

        // Mock current time to 2nd of the month
        Carbon::setTestNow(Carbon::create(2025, 1, 2));
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hris/dashboard/daily-quote');
        $quote2 = $response2->json('data.quote');

        $this->assertNotEquals($quote1, $quote2);

        Carbon::setTestNow(); // Reset test now
    }
}
