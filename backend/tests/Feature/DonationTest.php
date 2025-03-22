<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Cause;
use App\Models\Donation;
use Illuminate\Support\Str;

class DonationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->seed(\Database\Seeders\CategoriesTableSeeder::class);
    }

    public function test_donor_can_make_donation(): void
    {
        // Create a donor user
        $user = User::factory()->create();
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Create a cause
        $cause = Cause::factory()->create();

        // Authenticate the user
        $this->actingAs($user);

        // Make a donation request
        $response = $this->postJson('/api/donations', [
            'cause_id' => $cause->id,
            'donation_amount' => 100.00,
        ]);

        // Assert successful response
        $response->assertStatus(201)
                ->assertJsonPath('data.donation_amount', 100.00)
                ->assertJsonPath('data.cause_id', $cause->id)
                ->assertJsonPath('data.user_id', $user->id);

        // Assert donation was created in the database
        $this->assertDatabaseHas('donations', [
            'cause_id' => $cause->id,
            'user_id' => $user->id,
            'donation_amount' => 100.00,
        ]);
    }

    public function test_guest_cannot_make_donation_to_nonexistent_cause(): void
    {
        // Generate a UUID that doesn't exist in the database
        $nonExistentCauseId = Str::uuid();

        // Make a donation request without authentication
        $response = $this->postJson('/api/donations', [
            'cause_id' => $nonExistentCauseId,
            'donation_amount' => 50.00,
        ]);

        // Assert error response
        $response->assertStatus(422);

        // Assert donation was not created in the database
        $this->assertDatabaseMissing('donations', [
            'cause_id' => $nonExistentCauseId,
            'donation_amount' => 50.00,
        ]);
    }

    public function test_user_can_list_their_donations(): void
    {
        // Create a donor user
        $user = User::factory()->create();
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Create a cause
        $cause = Cause::factory()->create();

        // Create donations for this user
        Donation::factory()->count(3)->create([
            'user_id' => $user->id,
            'cause_id' => $cause->id,
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Request user's donations
        $response = $this->getJson('/api/donations');

        // Assert successful response with 3 donations
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }
} 