<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Cause;
use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Support\Str;

class DonationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DonationService $donationService;
    protected User $donor;
    protected Cause $cause;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->seed(\Database\Seeders\CategoriesTableSeeder::class);
        
        // Create a donor user
        $this->donor = User::factory()->create();
        $this->donor->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );
        
        // Create a cause
        $this->cause = Cause::factory()->create([
            'raised_amount' => 0,
            'goal_amount' => 10000,
        ]);
        
        // Create the donation service
        $this->donationService = app(DonationService::class);
    }

    public function test_create_donation(): void
    {
        // Create donation data
        $donationData = [
            'user_id' => $this->donor->id,
            'cause_id' => $this->cause->id,
            'donation_amount' => 100.00,
        ];
        
        // Create the donation
        $donation = $this->donationService->createDonation($donationData);
        
        // Assert donation was created
        $this->assertInstanceOf(Donation::class, $donation);
        $this->assertEquals($this->donor->id, $donation->user_id);
        $this->assertEquals($this->cause->id, $donation->cause_id);
        $this->assertEquals(100.00, $donation->donation_amount);
        
        // Refresh the cause from the database
        $this->cause->refresh();
        
        // Assert cause raised amount was updated
        $this->assertEquals(100.00, $this->cause->raised_amount);
    }
    
    public function test_get_user_donations(): void
    {
        // Create 3 donations for this user
        Donation::factory()->count(3)->create([
            'user_id' => $this->donor->id,
            'cause_id' => $this->cause->id,
        ]);
        
        // Create 2 donations for another user
        $anotherUser = User::factory()->create();
        Donation::factory()->count(2)->create([
            'user_id' => $anotherUser->id,
            'cause_id' => $this->cause->id,
        ]);
        
        // Get donations for this user
        $donations = $this->donationService->getUserDonations($this->donor->id);
        
        // Assert correct number of donations
        $this->assertCount(3, $donations);
        
        // Assert all donations belong to this user
        foreach ($donations as $donation) {
            $this->assertEquals($this->donor->id, $donation->user_id);
        }
    }
    
    public function test_get_cause_donations(): void
    {
        // Create 4 donations for this cause
        Donation::factory()->count(4)->create([
            'cause_id' => $this->cause->id,
        ]);
        
        // Create another cause with 2 donations
        $anotherCause = Cause::factory()->create();
        Donation::factory()->count(2)->create([
            'cause_id' => $anotherCause->id,
        ]);
        
        // Get donations for this cause
        $donations = $this->donationService->getCauseDonations($this->cause->id);
        
        // Assert correct number of donations
        $this->assertCount(4, $donations);
        
        // Assert all donations belong to this cause
        foreach ($donations as $donation) {
            $this->assertEquals($this->cause->id, $donation->cause_id);
        }
    }
} 