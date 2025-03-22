<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Cause;
use App\Models\CauseUpdate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CauseUpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $cause;
    protected $admin;
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        
        // Use disk for testing
        Storage::fake('public');
        
        // Create a cause
        $this->cause = Cause::factory()->create();
        
        // Create an admin user
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );
        
        // Create a cause manager
        $this->manager = User::factory()->create();
        $this->manager->roles()->attach(
            \App\Models\Role::where('role_name', 'Cause Manager')->first()->id
        );
        
        // Assign manager to the cause
        $this->cause->managers()->attach($this->manager->id);
    }

    public function test_guest_can_list_cause_updates(): void
    {
        // Create some updates for this cause
        CauseUpdate::factory()
            ->count(5)
            ->create([
                'cause_id' => $this->cause->id,
                'author_id' => $this->manager->id,
            ]);

        // Request list of updates for this cause
        $response = $this->getJson("/api/causes/{$this->cause->id}/updates");

        // Assert successful response with 5 updates
        $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
    }

    public function test_guest_can_view_single_update(): void
    {
        // Create an update
        $update = CauseUpdate::factory()->create([
            'cause_id' => $this->cause->id,
            'author_id' => $this->manager->id,
            'title' => 'Test Update',
            'content' => 'Test update content',
        ]);

        // Request the update
        $response = $this->getJson("/api/causes/updates/{$update->id}");

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.title', 'Test Update')
                ->assertJsonPath('data.content', 'Test update content');
    }

    public function test_manager_can_create_update(): void
    {
        // Authenticate as manager
        $this->actingAs($this->manager);

        // Create a media file
        $mediaFile = UploadedFile::fake()->image('update-image.jpg');

        // Create an update
        $response = $this->postJson("/api/causes/{$this->cause->id}/updates", [
            'title' => 'New Update',
            'content' => 'Content for new update',
            'update_type' => 'progress',
            'media' => [$mediaFile],
        ]);

        // Assert successful response
        $response->assertStatus(201)
                ->assertJsonPath('data.title', 'New Update')
                ->assertJsonPath('data.content', 'Content for new update');

        // Assert update was created in the database
        $this->assertDatabaseHas('cause_updates', [
            'cause_id' => $this->cause->id,
            'author_id' => $this->manager->id,
            'title' => 'New Update',
            'content' => 'Content for new update',
            'update_type' => 'progress',
        ]);
    }

    public function test_admin_can_create_update_for_any_cause(): void
    {
        // Authenticate as admin
        $this->actingAs($this->admin);

        // Create an update
        $response = $this->postJson("/api/causes/{$this->cause->id}/updates", [
            'title' => 'Admin Update',
            'content' => 'Admin update content',
            'update_type' => 'milestone',
        ]);

        // Assert successful response
        $response->assertStatus(201)
                ->assertJsonPath('data.title', 'Admin Update');

        // Assert update was created in the database
        $this->assertDatabaseHas('cause_updates', [
            'cause_id' => $this->cause->id,
            'author_id' => $this->admin->id,
            'title' => 'Admin Update',
            'update_type' => 'milestone',
        ]);
    }

    public function test_manager_can_update_their_update(): void
    {
        // Create an update by the manager
        $update = CauseUpdate::factory()->create([
            'cause_id' => $this->cause->id,
            'author_id' => $this->manager->id,
            'title' => 'Original Title',
            'content' => 'Original content',
        ]);

        // Authenticate as manager
        $this->actingAs($this->manager);

        // Update the update
        $response = $this->putJson("/api/causes/updates/{$update->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.title', 'Updated Title')
                ->assertJsonPath('data.content', 'Updated content');

        // Assert update was updated in the database
        $this->assertDatabaseHas('cause_updates', [
            'id' => $update->id,
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);
    }

    public function test_admin_can_delete_any_update(): void
    {
        // Create an update by the manager
        $update = CauseUpdate::factory()->create([
            'cause_id' => $this->cause->id,
            'author_id' => $this->manager->id,
        ]);

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Delete the update
        $response = $this->deleteJson("/api/causes/updates/{$update->id}");

        // Assert successful response
        $response->assertStatus(200);

        // Assert update was deleted (or soft-deleted) from the database
        $this->assertSoftDeleted('cause_updates', [
            'id' => $update->id,
        ]);
    }

    public function test_non_manager_cannot_create_update(): void
    {
        // Create a regular user (not a manager of this cause)
        $user = User::factory()->create();
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Authenticate as non-manager
        $this->actingAs($user);

        // Attempt to create an update
        $response = $this->postJson("/api/causes/{$this->cause->id}/updates", [
            'title' => 'Unauthorized Update',
            'content' => 'Content',
        ]);

        // Assert forbidden response
        $response->assertStatus(403);

        // Assert update was not created
        $this->assertDatabaseMissing('cause_updates', [
            'title' => 'Unauthorized Update',
        ]);
    }
} 