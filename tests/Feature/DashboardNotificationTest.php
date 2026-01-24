<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Hris\Employee;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Notifications\WelcomeEmployee;
use Illuminate\Support\Facades\Notification;

class DashboardNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
        ]);

        // Create a user and employee
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
        ]);

        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Mock current tenant
        $this->actingAs($this->user);
    }

    public function test_can_fetch_notifications()
    {
        // Send a notification
        $this->user->notify(new WelcomeEmployee('STAFF/001', true));

        $response = $this->getJson('/api/hris/dashboard/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'message',
                        'timestamp',
                        'read',
                        'action_url',
                        'action_text',
                    ]
                ]
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_unread_count()
    {
        // Send 2 notifications
        $this->user->notify(new WelcomeEmployee('STAFF/001', true));
        $this->user->notify(new WelcomeEmployee('STAFF/002', true));

        $response = $this->getJson('/api/hris/dashboard/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'count' => 2
                ]
            ]);
    }

    public function test_can_mark_notification_as_read()
    {
        $this->user->notify(new WelcomeEmployee('STAFF/001', true));
        $notification = $this->user->unreadNotifications->first();

        $response = $this->patchJson("/api/hris/dashboard/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->fresh()->unreadNotifications->count());
    }

    public function test_can_mark_all_notifications_as_read()
    {
        $this->user->notify(new WelcomeEmployee('STAFF/001', true));
        $this->user->notify(new WelcomeEmployee('STAFF/002', true));

        $this->assertEquals(2, $this->user->unreadNotifications->count());

        $response = $this->patchJson('/api/hris/dashboard/notifications/read-all');

        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->fresh()->unreadNotifications->count());
    }
}
