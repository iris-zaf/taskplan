<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GuestTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(User $user, array $overrides = [])
    {
        return $user->events()->create(array_merge([
            'title' => 'Product Launch',
            'event_date' => '2027-01-15 18:00:00',
        ], $overrides));
    }

    public function test_owner_can_add_a_guest_and_gets_a_unique_code(): void
    {
        $user = User::factory()->create();
        $event = $this->makeEvent($user);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/events/{$event->id}/guests", [
            'name' => 'Alex Guest',
            'email' => 'alex@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Alex Guest', 'email' => 'alex@example.com'])
            ->assertJsonStructure(['code']);

        $this->assertDatabaseHas('guests', ['event_id' => $event->id, 'email' => 'alex@example.com']);
        $this->assertNotEmpty($response->json('code'));
    }

    public function test_guest_requires_name_and_email(): void
    {
        $user = User::factory()->create();
        $event = $this->makeEvent($user);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/events/{$event->id}/guests", []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_user_cannot_add_guest_to_another_users_event(): void
    {
        $owner = User::factory()->create();
        $event = $this->makeEvent($owner);

        $intruder = User::factory()->create();
        Sanctum::actingAs($intruder);

        $this->postJson("/api/events/{$event->id}/guests", [
            'name' => 'Alex Guest',
            'email' => 'alex@example.com',
        ])->assertStatus(404);
    }

    public function test_owner_can_list_guests_for_their_event(): void
    {
        $user = User::factory()->create();
        $event = $this->makeEvent($user);
        $event->guests()->create(['name' => 'Alex', 'email' => 'alex@example.com']);
        $event->guests()->create(['name' => 'Blair', 'email' => 'blair@example.com']);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/events/{$event->id}/guests");

        $response->assertStatus(200)->assertJsonCount(2);
    }

    public function test_owner_can_remove_a_guest(): void
    {
        $user = User::factory()->create();
        $event = $this->makeEvent($user);
        $guest = $event->guests()->create(['name' => 'Alex', 'email' => 'alex@example.com']);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/events/{$event->id}/guests/{$guest->id}")->assertStatus(204);
        $this->assertDatabaseMissing('guests', ['id' => $guest->id]);
    }

    public function test_checkin_marks_guest_as_checked_in(): void
    {
        $user = User::factory()->create();
        $event = $this->makeEvent($user);
        $guest = $event->guests()->create(['name' => 'Alex', 'email' => 'alex@example.com']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/guests/checkin', ['code' => $guest->code]);

        $response->assertStatus(200)
            ->assertJsonPath('already_checked_in', false)
            ->assertJsonPath('guest.name', 'Alex');

        $this->assertNotNull($guest->fresh()->checked_in_at);
    }

    public function test_checkin_is_idempotent_for_already_checked_in_guest(): void
    {
        $user = User::factory()->create();
        $event = $this->makeEvent($user);
        $guest = $event->guests()->create(['name' => 'Alex', 'email' => 'alex@example.com']);
        $guest->checked_in_at = now();
        $guest->save();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/guests/checkin', ['code' => $guest->code]);

        $response->assertStatus(200)->assertJsonPath('already_checked_in', true);
    }

    public function test_checkin_fails_for_unknown_code(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/guests/checkin', ['code' => 'does-not-exist'])->assertStatus(404);
    }

    public function test_user_cannot_checkin_guest_from_another_users_event(): void
    {
        $owner = User::factory()->create();
        $event = $this->makeEvent($owner);
        $guest = $event->guests()->create(['name' => 'Alex', 'email' => 'alex@example.com']);

        $intruder = User::factory()->create();
        Sanctum::actingAs($intruder);

        $this->postJson('/api/guests/checkin', ['code' => $guest->code])->assertStatus(404);
        $this->assertNull($guest->fresh()->checked_in_at);
    }

    public function test_ticket_endpoint_returns_guest_info_without_auth(): void
    {
        $user = User::factory()->create();
        $event = $this->makeEvent($user, ['location' => 'Berlin']);
        $guest = $event->guests()->create(['name' => 'Alex', 'email' => 'alex@example.com']);

        $response = $this->getJson("/api/guests/ticket/{$guest->code}");

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Alex',
                'code' => $guest->code,
                'event' => ['title' => 'Product Launch', 'location' => 'Berlin'],
            ]);
    }

    public function test_ticket_endpoint_404_for_unknown_code(): void
    {
        $this->getJson('/api/guests/ticket/does-not-exist')->assertStatus(404);
    }
}
