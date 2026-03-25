<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Passport\Passport;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test events require authentication to post.
     */
    public function test_cannot_create_event_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/events', [
            'name' => 'Test Event',
            'organizer' => 'Test Org',
            'description' => 'A new event',
            'url' => 'https://example.com'
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test validation rules.
     */
    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/events', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['organizer', 'description', 'url']);
    }

    /**
     * Test public index works.
     */
    public function test_can_fetch_events_publicly(): void
    {
        $response = $this->getJson('/api/v1/events');

        $response->assertStatus(200);
    }
}
