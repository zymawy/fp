<?php

use App\Models\Category;
use App\Models\Cause;
use App\Models\Donation;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Relationship & Attribute Tests
|--------------------------------------------------------------------------
|
| Verifies model relationships, UUID generation, attribute casting,
| and helper methods at the unit level.
|
*/

// ── UUID Generation ───────────────────────────────────────────────────────

it('generates a UUID primary key for User', function () {
    $user = User::factory()->create();

    expect($user->id)->not->toBeNull();
    expect(Str::isUuid($user->id))->toBeTrue();
});

it('generates a UUID primary key for Category', function () {
    $category = Category::factory()->create();

    expect($category->id)->not->toBeNull();
    expect(Str::isUuid($category->id))->toBeTrue();
});

it('generates a UUID primary key for Cause', function () {
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);

    expect($cause->id)->not->toBeNull();
    expect(Str::isUuid($cause->id))->toBeTrue();
});

it('generates a UUID primary key for Donation', function () {
    $user     = User::factory()->create();
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);
    $donation = Donation::factory()->create([
        'user_id'  => $user->id,
        'cause_id' => $cause->id,
    ]);

    expect($donation->id)->not->toBeNull();
    expect(Str::isUuid($donation->id))->toBeTrue();
});

// ── User Relationships ────────────────────────────────────────────────────

it('User has many donations', function () {
    $user     = User::factory()->create();
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);

    Donation::factory()->count(3)->create([
        'user_id'  => $user->id,
        'cause_id' => $cause->id,
    ]);

    expect($user->donations)->toHaveCount(3);
    expect($user->donations->first())->toBeInstanceOf(Donation::class);
});

it('User has many roles via pivot', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['role_name' => 'Donor']);
    $user->roles()->attach($role->id, ['id' => Str::uuid()->toString()]);

    $user->load('roles');

    expect($user->roles)->toHaveCount(1);
    expect($user->roles->first())->toBeInstanceOf(Role::class);
    expect($user->roles->first()->role_name)->toBe('Donor');
});

it('User isAdmin returns true for admin role', function () {
    $user     = User::factory()->create();
    $adminRole = Role::factory()->create(['role_name' => 'Admin']);
    $user->roles()->attach($adminRole->id, ['id' => Str::uuid()->toString()]);

    $user->load('roles');

    expect($user->isAdmin())->toBeTrue();
});

it('User isAdmin returns false for non-admin role', function () {
    $user     = User::factory()->create();
    $donorRole = Role::factory()->create(['role_name' => 'Donor']);
    $user->roles()->attach($donorRole->id, ['id' => Str::uuid()->toString()]);

    $user->load('roles');

    expect($user->isAdmin())->toBeFalse();
});

// ── Cause Relationships ──────────────────────────────────────────────────

it('Cause belongs to Category', function () {
    $category = Category::factory()->create(['name' => 'Education']);
    $cause    = Cause::factory()->create(['category_id' => $category->id]);

    expect($cause->category)->toBeInstanceOf(Category::class);
    expect($cause->category->name)->toBe('Education');
});

it('Cause has many donations', function () {
    $user     = User::factory()->create();
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);

    Donation::factory()->count(4)->create([
        'user_id'  => $user->id,
        'cause_id' => $cause->id,
    ]);

    expect($cause->donations)->toHaveCount(4);
    expect($cause->donations->first())->toBeInstanceOf(Donation::class);
});

it('Category has many causes', function () {
    $category = Category::factory()->create();
    Cause::factory()->count(3)->create(['category_id' => $category->id]);

    expect($category->causes)->toHaveCount(3);
    expect($category->causes->first())->toBeInstanceOf(Cause::class);
});

// ── Donation Relationships ────────────────────────────────────────────────

it('Donation belongs to User', function () {
    $user     = User::factory()->create(['name' => 'Generous Donor']);
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);
    $donation = Donation::factory()->create([
        'user_id'  => $user->id,
        'cause_id' => $cause->id,
    ]);

    expect($donation->user)->toBeInstanceOf(User::class);
    expect($donation->user->name)->toBe('Generous Donor');
});

it('Donation belongs to Cause', function () {
    $user     = User::factory()->create();
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create([
        'title'       => 'Test Cause',
        'category_id' => $category->id,
    ]);
    $donation = Donation::factory()->create([
        'user_id'  => $user->id,
        'cause_id' => $cause->id,
    ]);

    expect($donation->cause)->toBeInstanceOf(Cause::class);
    expect($donation->cause->title)->toBe('Test Cause');
});

it('Donation has one Transaction', function () {
    $user     = User::factory()->create();
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);
    $donation = Donation::factory()->create([
        'user_id'  => $user->id,
        'cause_id' => $cause->id,
    ]);

    Transaction::factory()->create([
        'donation_id' => $donation->id,
    ]);

    expect($donation->transaction)->toBeInstanceOf(Transaction::class);
});

// ── Transaction Relationships ─────────────────────────────────────────────

it('Transaction belongs to Donation', function () {
    $user     = User::factory()->create();
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);
    $donation = Donation::factory()->create([
        'user_id'  => $user->id,
        'cause_id' => $cause->id,
    ]);

    $transaction = Transaction::factory()->create([
        'donation_id' => $donation->id,
    ]);

    expect($transaction->donation)->toBeInstanceOf(Donation::class);
    expect($transaction->donation->id)->toBe($donation->id);
});

// ── Attribute Casting ─────────────────────────────────────────────────────

it('Donation casts amount to decimal', function () {
    $user     = User::factory()->create();
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);
    $donation = Donation::factory()->create([
        'user_id'  => $user->id,
        'cause_id' => $cause->id,
        'amount'   => 99.99,
    ]);

    $donation->refresh();
    expect($donation->amount)->toBe('99.99');
});

it('Donation casts is_anonymous to boolean', function () {
    $user     = User::factory()->create();
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create(['category_id' => $category->id]);
    $donation = Donation::factory()->create([
        'user_id'      => $user->id,
        'cause_id'     => $cause->id,
        'is_anonymous' => true,
    ]);

    $donation->refresh();
    expect($donation->is_anonymous)->toBeTrue();
    expect($donation->is_anonymous)->toBeBool();
});

it('Cause casts goal_amount to decimal', function () {
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create([
        'category_id' => $category->id,
        'goal_amount' => 5000.50,
    ]);

    $cause->refresh();
    expect($cause->goal_amount)->toBe('5000.50');
});

it('Cause casts is_featured to boolean', function () {
    $category = Category::factory()->create();
    $cause    = Cause::factory()->create([
        'category_id' => $category->id,
        'is_featured' => true,
    ]);

    $cause->refresh();
    expect($cause->is_featured)->toBeTrue();
    expect($cause->is_featured)->toBeBool();
});
