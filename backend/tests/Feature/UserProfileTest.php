<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UserProfileTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        
        // Use disk for testing
        Storage::fake('public');
    }

    public function test_user_can_view_their_profile(): void
    {
        // Create a user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        // Associate user with donor role
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Authenticate as the user
        $this->actingAs($user);

        // Request the user's profile
        $response = $this->getJson('/api/user/profile');

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.name', 'Test User')
                ->assertJsonPath('data.email', 'test@example.com');
    }

    public function test_user_can_update_their_profile(): void
    {
        // Create a user
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);
        
        // Associate user with donor role
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Authenticate as the user
        $this->actingAs($user);

        // Create an avatar image
        $avatarFile = UploadedFile::fake()->image('avatar.jpg');

        // Update the user's profile
        $response = $this->putJson('/api/user/profile', [
            'name' => 'Updated Name',
            'bio' => 'Updated bio information',
            'location' => 'London, UK',
            'avatar' => $avatarFile,
        ]);

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.name', 'Updated Name')
                ->assertJsonPath('data.bio', 'Updated bio information')
                ->assertJsonPath('data.location', 'London, UK');

        // Assert profile was updated in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'bio' => 'Updated bio information',
            'location' => 'London, UK',
        ]);

        // Assert avatar was stored
        $updatedUser = User::find($user->id);
        if ($updatedUser && $updatedUser->avatar) {
            Storage::disk('public')->assertExists($updatedUser->avatar);
        }
    }

    public function test_user_can_change_password(): void
    {
        // Create a user with a known password
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        
        // Associate user with donor role
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Authenticate as the user
        $this->actingAs($user);

        // Change the user's password
        $response = $this->postJson('/api/user/change-password', [
            'current_password' => 'old-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        // Assert successful response
        $response->assertStatus(200);

        // Refresh the user from the database
        $updatedUser = User::find($user->id);
        
        // Assert password was updated
        $this->assertTrue(Hash::check('new-secure-password', $updatedUser->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        // Create a user with a known password
        $user = User::factory()->create([
            'password' => Hash::make('actual-password'),
        ]);
        
        // Associate user with donor role
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Authenticate as the user
        $this->actingAs($user);

        // Attempt to change password with incorrect current password
        $response = $this->postJson('/api/user/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        // Assert validation error response
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['current_password']);

        // Refresh the user from the database
        $updatedUser = User::find($user->id);
        
        // Assert password was not updated
        $this->assertFalse(Hash::check('new-password', $updatedUser->password));
        $this->assertTrue(Hash::check('actual-password', $updatedUser->password));
    }

    public function test_user_can_delete_their_account(): void
    {
        // Create a user
        $user = User::factory()->create();
        
        // Associate user with donor role
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Authenticate as the user
        $this->actingAs($user);

        // Delete the user's account
        $response = $this->deleteJson('/api/user/account');

        // Assert successful response
        $response->assertStatus(200);

        // Assert user was deleted (or soft-deleted) from the database
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    public function test_guest_cannot_access_profile(): void
    {
        // Attempt to access profile without authentication
        $response = $this->getJson('/api/user/profile');

        // Assert unauthorized response
        $response->assertStatus(401);
    }

    public function test_admin_can_view_any_user_profile(): void
    {
        // Create an admin
        $admin = User::factory()->create();
        $admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );

        // Create a regular user
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
        ]);

        // Authenticate as admin
        $this->actingAs($admin);

        // Request the regular user's profile
        $response = $this->getJson("/api/admin/users/{$user->id}");

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.name', 'Regular User')
                ->assertJsonPath('data.email', 'regular@example.com');
    }
} 