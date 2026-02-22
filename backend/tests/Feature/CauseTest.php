<?php

use App\Models\Category;
use App\Models\Cause;
use App\Models\Donation;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Cause Tests
|--------------------------------------------------------------------------
|
| Covers: public listing, single cause view, relationships, filtering,
| and search on causes. All list/show routes are public (no auth needed).
|
*/

// ── List Causes (Public) ──────────────────────────────────────────────────

it('lists all causes without authentication', function () {
    $category = Category::factory()->create();
    Cause::factory()->count(5)->create(['category_id' => $category->id]);

    $response = $this->getJson('/api/causes', ['Accept' => 'application/json']);

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);

    expect(count($response->json('data')))->toBe(5);
});

it('returns an empty list when no causes exist', function () {
    $response = $this->getJson('/api/causes', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(0);
});

it('paginates causes with per_page parameter', function () {
    $category = Category::factory()->create();
    Cause::factory()->count(15)->create(['category_id' => $category->id]);

    $response = $this->getJson('/api/causes?per_page=5', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(5);
});

// ── Single Cause (Public) ─────────────────────────────────────────────────

it('shows a single cause by ID', function () {
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create([
        'title'       => 'Help Build a School',
        'category_id' => $category->id,
    ]);

    $response = $this->getJson("/api/causes/{$cause->id}", ['Accept' => 'application/json']);

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $cause->id)
        ->assertJsonPath('data.attributes.title', 'Help Build a School');
});

it('shows a single cause by slug', function () {
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create([
        'title'       => 'Clean Water Initiative',
        'slug'        => 'clean-water-initiative',
        'category_id' => $category->id,
    ]);

    $response = $this->getJson('/api/causes/clean-water-initiative', ['Accept' => 'application/json']);

    $response->assertStatus(200)
        ->assertJsonPath('data.attributes.slug', 'clean-water-initiative');
});

it('returns 404 for a non-existent cause', function () {
    $fakeId = \Illuminate\Support\Str::uuid()->toString();

    $response = $this->getJson("/api/causes/{$fakeId}", ['Accept' => 'application/json']);

    $response->assertStatus(404);
});

// ── Cause Includes Category Relationship ──────────────────────────────────

it('includes category data in cause response', function () {
    $category = Category::factory()->create(['name' => 'Education']);
    $cause    = Cause::factory()->create(['category_id' => $category->id]);

    $response = $this->getJson("/api/causes/{$cause->id}", ['Accept' => 'application/json']);

    $response->assertStatus(200)
        ->assertJsonPath('data.attributes.category_id', $category->id)
        ->assertJsonPath('data.attributes.category_name', 'Education');
});

// ── Cause Includes Donation Summary ───────────────────────────────────────

it('includes donations count when donations are loaded', function () {
    $user     = User::factory()->create();
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);

    Donation::factory()->count(3)->create([
        'user_id'  => $user->id,
        'cause_id' => $cause->id,
    ]);

    $response = $this->getJson("/api/causes/{$cause->id}", ['Accept' => 'application/json']);

    $response->assertStatus(200)
        ->assertJsonPath('data.attributes.donations_count', 3);
});

// ── Filtering ─────────────────────────────────────────────────────────────

it('filters causes by category_id', function () {
    $cat1 = Category::factory()->create();
    $cat2 = Category::factory()->create();

    Cause::factory()->count(3)->create(['category_id' => $cat1->id]);
    Cause::factory()->count(2)->create(['category_id' => $cat2->id]);

    $response = $this->getJson("/api/causes?category_id={$cat1->id}", ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(3);
});

it('filters causes by status', function () {
    $category = Category::factory()->create();

    Cause::factory()->count(2)->create(['category_id' => $category->id, 'status' => 'active']);
    Cause::factory()->count(1)->create(['category_id' => $category->id, 'status' => 'completed']);

    $response = $this->getJson('/api/causes?status=active', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(2);
});

it('filters causes by is_featured', function () {
    $category = Category::factory()->create();

    Cause::factory()->count(2)->create(['category_id' => $category->id, 'is_featured' => true]);
    Cause::factory()->count(3)->create(['category_id' => $category->id, 'is_featured' => false]);

    $response = $this->getJson('/api/causes?is_featured=1', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(2);
});

// ── Search ────────────────────────────────────────────────────────────────

it('searches causes by title', function () {
    $category = Category::factory()->create();

    Cause::factory()->create(['title' => 'Help Build a School', 'category_id' => $category->id]);
    Cause::factory()->create(['title' => 'Clean Water Project', 'category_id' => $category->id]);
    Cause::factory()->create(['title' => 'Feed the Hungry', 'category_id' => $category->id]);

    $response = $this->getJson('/api/causes?search=School', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
});

it('searches causes by description', function () {
    $category = Category::factory()->create();

    Cause::factory()->create([
        'title'       => 'Generic Title',
        'description' => 'This cause helps orphaned children find loving families.',
        'category_id' => $category->id,
    ]);
    Cause::factory()->create([
        'title'       => 'Another Title',
        'description' => 'Provides medical supplies to remote communities.',
        'category_id' => $category->id,
    ]);

    $response = $this->getJson('/api/causes?search=orphaned', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
});
