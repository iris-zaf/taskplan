<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Product Launch',
            'description' => 'Launching the new product',
            'event_date' => '2027-01-15 18:00:00',
            'location' => 'Berlin',
            'guest_count' => 40,
        ], $overrides);
    }

    public function test_guest_cannot_access_events_without_authentication(): void
    {
        $this->getJson('/api/events')->assertStatus(401);
        $this->postJson('/api/events', $this->validPayload())->assertStatus(401);
    }

    public function test_user_only_sees_their_own_events(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->events()->create($this->validPayload(['title' => 'My Event']));
        $otherUser->events()->create($this->validPayload(['title' => "Someone Else's Event"]));

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)->assertJsonCount(1)->assertJsonFragment(['title' => 'My Event']);
    }

    public function test_user_can_create_an_event(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/events', $this->validPayload());

        $response->assertStatus(201)->assertJsonFragment(['title' => 'Product Launch']);
        $this->assertDatabaseHas('events', ['title' => 'Product Launch', 'user_id' => $user->id]);
    }

    public function test_event_creation_requires_title_and_date(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/events', ['location' => 'Berlin']);

        $response->assertStatus(422)->assertJsonValidationErrors(['title', 'event_date']);
    }

    public function test_user_can_view_their_own_event(): void
    {
        $user = User::factory()->create();
        $event = $user->events()->create($this->validPayload());
        Sanctum::actingAs($user);

        $this->getJson("/api/events/{$event->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Product Launch']);
    }

    public function test_user_cannot_view_another_users_event(): void
    {
        $owner = User::factory()->create();
        $event = $owner->events()->create($this->validPayload());

        $intruder = User::factory()->create();
        Sanctum::actingAs($intruder);

        $this->getJson("/api/events/{$event->id}")->assertStatus(404);
    }

    public function test_user_can_update_their_own_event(): void
    {
        $user = User::factory()->create();
        $event = $user->events()->create($this->validPayload());
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/events/{$event->id}", ['title' => 'Updated Title']);

        $response->assertStatus(200)->assertJsonFragment(['title' => 'Updated Title']);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated Title']);
    }

    public function test_user_cannot_update_another_users_event(): void
    {
        $owner = User::factory()->create();
        $event = $owner->events()->create($this->validPayload());

        $intruder = User::factory()->create();
        Sanctum::actingAs($intruder);

        $this->putJson("/api/events/{$event->id}", ['title' => 'Hacked'])->assertStatus(404);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Product Launch']);
    }

    public function test_user_can_delete_their_own_event(): void
    {
        $user = User::factory()->create();
        $event = $user->events()->create($this->validPayload());
        Sanctum::actingAs($user);

        $this->deleteJson("/api/events/{$event->id}")->assertStatus(204);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_user_cannot_delete_another_users_event(): void
    {
        $owner = User::factory()->create();
        $event = $owner->events()->create($this->validPayload());

        $intruder = User::factory()->create();
        Sanctum::actingAs($intruder);

        $this->deleteJson("/api/events/{$event->id}")->assertStatus(404);
        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }
}
