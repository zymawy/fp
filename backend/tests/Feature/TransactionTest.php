<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Cause;
use App\Models\Donation;
use App\Models\Transaction;
use Illuminate\Support\Str;

class TransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->seed(\Database\Seeders\CategoriesTableSeeder::class);
    }

    public function test_admin_can_view_all_transactions(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );

        // Create a donor
        $donor = User::factory()->create();
        $donor->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Create a cause
        $cause = Cause::factory()->create();

        // Create a donation
        $donation = Donation::factory()->create([
            'user_id' => $donor->id,
            'cause_id' => $cause->id,
            'donation_amount' => 100.00,
        ]);

        // Create transactions
        Transaction::factory()->count(5)->create([
            'donation_id' => $donation->id,
        ]);

        // Authenticate as admin
        $this->actingAs($admin);

        // Request transactions
        $response = $this->getJson('/api/transactions');

        // Assert successful response with 5 transactions
        $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
    }

    public function test_donor_can_view_their_transactions(): void
    {
        // Create a donor
        $donor = User::factory()->create();
        $donor->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Create a cause
        $cause = Cause::factory()->create();

        // Create donations for this donor
        $donation1 = Donation::factory()->create([
            'user_id' => $donor->id,
            'cause_id' => $cause->id,
            'donation_amount' => 50.00,
        ]);

        $donation2 = Donation::factory()->create([
            'user_id' => $donor->id,
            'cause_id' => $cause->id,
            'donation_amount' => 75.00,
        ]);

        // Create transactions for these donations
        Transaction::factory()->create([
            'donation_id' => $donation1->id,
            'payment_method' => 'credit_card',
            'payment_status' => 'completed',
        ]);

        Transaction::factory()->create([
            'donation_id' => $donation2->id,
            'payment_method' => 'paypal',
            'payment_status' => 'completed',
        ]);

        // Create another donor with a donation and transaction
        $anotherDonor = User::factory()->create();
        $anotherDonation = Donation::factory()->create([
            'user_id' => $anotherDonor->id,
            'cause_id' => $cause->id,
        ]);
        Transaction::factory()->create([
            'donation_id' => $anotherDonation->id,
        ]);

        // Authenticate as the donor
        $this->actingAs($donor);

        // Request transactions
        $response = $this->getJson('/api/transactions');

        // Assert successful response with only the donor's 2 transactions
        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }

    public function test_guest_cannot_view_transactions(): void
    {
        // Create some transactions
        $donation = Donation::factory()->create();
        Transaction::factory()->count(3)->create([
            'donation_id' => $donation->id,
        ]);

        // Try to access transactions without authentication
        $response = $this->getJson('/api/transactions');

        // Assert unauthorized response
        $response->assertStatus(401);
    }

    public function test_can_view_single_transaction(): void
    {
        // Create a donor
        $donor = User::factory()->create();
        $donor->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );

        // Create a donation
        $donation = Donation::factory()->create([
            'user_id' => $donor->id,
        ]);

        // Create a transaction
        $transaction = Transaction::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => 'credit_card',
            'payment_status' => 'completed',
            'transaction_id' => 'TXN-' . uniqid(),
        ]);

        // Authenticate as the donor
        $this->actingAs($donor);

        // Request the transaction
        $response = $this->getJson("/api/transactions/{$transaction->id}");

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.payment_method', 'credit_card')
                ->assertJsonPath('data.payment_status', 'completed');
    }
} 