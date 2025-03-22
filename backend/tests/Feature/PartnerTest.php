<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Partner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PartnerTest extends TestCase
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

    public function test_guest_can_list_partners(): void
    {
        // Create some partners
        Partner::factory()->count(5)->create();

        // Request list of partners
        $response = $this->getJson('/api/partners');

        // Assert successful response with 5 partners
        $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
    }

    public function test_guest_can_view_single_partner(): void
    {
        // Create a partner
        $partner = Partner::factory()->create([
            'name' => 'Test Partner',
            'description' => 'Test partner description',
            'partnership_level' => 'Gold',
        ]);

        // Request the partner
        $response = $this->getJson("/api/partners/{$partner->id}");

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.name', 'Test Partner')
                ->assertJsonPath('data.description', 'Test partner description')
                ->assertJsonPath('data.partnership_level', 'Gold');
    }

    public function test_admin_can_create_partner(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );

        // Authenticate as admin
        $this->actingAs($admin);

        // Create a logo image
        $logoFile = UploadedFile::fake()->image('partner-logo.jpg');

        // Create a partner
        $response = $this->postJson('/api/partners', [
            'name' => 'New Partner',
            'description' => 'Description for new partner',
            'partnership_level' => 'Silver',
            'website_url' => 'https://example.com',
            'logo' => $logoFile,
        ]);

        // Assert successful response
        $response->assertStatus(201)
                ->assertJsonPath('data.name', 'New Partner')
                ->assertJsonPath('data.partnership_level', 'Silver');

        // Assert partner was created in the database
        $this->assertDatabaseHas('partners', [
            'name' => 'New Partner',
            'description' => 'Description for new partner',
            'partnership_level' => 'Silver',
            'website_url' => 'https://example.com',
        ]);

        // Assert logo was stored
        $partner = Partner::where('name', 'New Partner')->first();
        if ($partner && $partner->logo) {
            Storage::disk('public')->assertExists($partner->logo);
        }
    }

    public function test_admin_can_update_partner(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );

        // Create a partner
        $partner = Partner::factory()->create([
            'name' => 'Original Partner',
            'description' => 'Original description',
            'partnership_level' => 'Bronze',
        ]);

        // Authenticate as admin
        $this->actingAs($admin);

        // Update the partner
        $response = $this->putJson("/api/partners/{$partner->id}", [
            'name' => 'Updated Partner',
            'description' => 'Updated description',
            'partnership_level' => 'Platinum',
            'website_url' => 'https://updated-example.com',
        ]);

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.name', 'Updated Partner')
                ->assertJsonPath('data.partnership_level', 'Platinum');

        // Assert partner was updated in the database
        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'name' => 'Updated Partner',
            'description' => 'Updated description',
            'partnership_level' => 'Platinum',
            'website_url' => 'https://updated-example.com',
        ]);
    }

    public function test_admin_can_delete_partner(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );

        // Create a partner
        $partner = Partner::factory()->create();

        // Authenticate as admin
        $this->actingAs($admin);

        // Delete the partner
        $response = $this->deleteJson("/api/partners/{$partner->id}");

        // Assert successful response
        $response->assertStatus(200);

        // Assert partner was deleted (or soft-deleted) from the database
        $this->assertSoftDeleted('partners', [
            'id' => $partner->id,
        ]);
    }

    public function test_non_admin_cannot_create_partner(): void
    {
        // Create a regular user
        $user = User::factory()->create();
        $user->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Authenticate as non-admin
        $this->actingAs($user);

        // Attempt to create a partner
        $response = $this->postJson('/api/partners', [
            'name' => 'Unauthorized Partner',
            'description' => 'Description',
            'partnership_level' => 'Gold',
        ]);

        // Assert forbidden response
        $response->assertStatus(403);

        // Assert partner was not created
        $this->assertDatabaseMissing('partners', [
            'name' => 'Unauthorized Partner',
        ]);
    }
} 