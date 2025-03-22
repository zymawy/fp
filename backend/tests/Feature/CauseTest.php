<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Cause;
use App\Models\Category;

class CauseTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->seed(\Database\Seeders\CategoriesTableSeeder::class);
    }

    public function test_guest_can_list_causes(): void
    {
        // Create some causes
        Cause::factory()->count(5)->create();

        // Request list of causes
        $response = $this->getJson('/api/causes');

        // Assert successful response with 5 causes
        $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
    }

    public function test_guest_can_view_single_cause(): void
    {
        // Create a cause
        $cause = Cause::factory()->create([
            'title' => 'Test Cause',
            'description' => 'This is a test cause',
        ]);

        // Request the cause
        $response = $this->getJson("/api/causes/{$cause->id}");

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.title', 'Test Cause')
                ->assertJsonPath('data.description', 'This is a test cause');
    }

    public function test_admin_can_create_cause(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );

        // Get a category
        $category = Category::first();

        // Authenticate as admin
        $this->actingAs($admin);

        // Create a cause
        $response = $this->postJson('/api/causes', [
            'title' => 'New Cause',
            'description' => 'Description for new cause',
            'goal_amount' => 5000,
            'category_id' => $category->id,
            'urgency_level' => 'medium',
            'status' => 'active',
        ]);

        // Assert successful response
        $response->assertStatus(201)
                ->assertJsonPath('data.title', 'New Cause');

        // Assert cause was created in the database
        $this->assertDatabaseHas('causes', [
            'title' => 'New Cause',
            'description' => 'Description for new cause',
        ]);
    }

    public function test_guest_cannot_create_cause(): void
    {
        // Get a category
        $category = Category::first();

        // Attempt to create a cause without authentication
        $response = $this->postJson('/api/causes', [
            'title' => 'Unauthorized Cause',
            'description' => 'Description',
            'goal_amount' => 1000,
            'category_id' => $category->id,
        ]);

        // Assert unauthorized response
        $response->assertStatus(401);

        // Assert cause was not created
        $this->assertDatabaseMissing('causes', [
            'title' => 'Unauthorized Cause',
        ]);
    }
} 