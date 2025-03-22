<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }

    public function test_guest_can_list_categories(): void
    {
        // Create some categories
        Category::factory()->count(5)->create();

        // Request list of categories
        $response = $this->getJson('/api/categories');

        // Assert successful response with 5 categories
        $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
    }

    public function test_guest_can_view_single_category(): void
    {
        // Create a category
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'description' => 'Test category description',
        ]);

        // Request the category
        $response = $this->getJson("/api/categories/{$category->id}");

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.name', 'Test Category')
                ->assertJsonPath('data.description', 'Test category description');
    }

    public function test_admin_can_create_category(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );

        // Authenticate as admin
        $this->actingAs($admin);

        // Create a category
        $response = $this->postJson('/api/categories', [
            'name' => 'New Category',
            'description' => 'Description for new category',
            'icon' => 'icon-class',
        ]);

        // Assert successful response
        $response->assertStatus(201)
                ->assertJsonPath('data.name', 'New Category');

        // Assert category was created in the database
        $this->assertDatabaseHas('categories', [
            'name' => 'New Category',
            'description' => 'Description for new category',
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );

        // Create a category
        $category = Category::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);

        // Authenticate as admin
        $this->actingAs($admin);

        // Update the category
        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'icon' => 'updated-icon',
        ]);

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.name', 'Updated Name')
                ->assertJsonPath('data.description', 'Updated description');

        // Assert category was updated in the database
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );

        // Create a category
        $category = Category::factory()->create();

        // Authenticate as admin
        $this->actingAs($admin);

        // Delete the category
        $response = $this->deleteJson("/api/categories/{$category->id}");

        // Assert successful response
        $response->assertStatus(200);

        // Assert category was deleted (or soft-deleted) from the database
        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_non_admin_cannot_create_category(): void
    {
        // Create a regular user
        $user = User::factory()->create();
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Authenticate as non-admin
        $this->actingAs($user);

        // Attempt to create a category
        $response = $this->postJson('/api/categories', [
            'name' => 'Unauthorized Category',
            'description' => 'Description',
        ]);

        // Assert forbidden response
        $response->assertStatus(403);

        // Assert category was not created
        $this->assertDatabaseMissing('categories', [
            'name' => 'Unauthorized Category',
        ]);
    }
} 