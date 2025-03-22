<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class TransactionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get donation IDs
        $donationIds = app()->has('seeded_donation_ids') 
            ? app()->get('seeded_donation_ids') 
            : DB::table('donations')->pluck('id')->toArray();
            
        $transactions = [];
        
        // Create a transaction for each donation
        foreach ($donationIds as $donationId) {
            $donation = DB::table('donations')->where('id', $donationId)->first();
            
            // Skip if donation not found
            if (!$donation) continue;
            
            $isSuccessful = $faker->boolean(80); // 80% chance of success
            
            $transactions[] = [
                'id' => Str::uuid()->toString(),
                'donation_id' => $donationId,
                'payment_method' => $faker->randomElement(['credit_card', 'paypal', 'bank_transfer', 'apple_pay', 'google_pay']),
                'payment_status' => $isSuccessful ? 'success' : $faker->randomElement(['pending', 'failed']),
                'transaction_id' => $faker->uuid,
                'timestamp' => $donation->created_at,
                'created_at' => $donation->created_at,
                'updated_at' => now(),
            ];
        }
        
        DB::table('transactions')->insert($transactions);
        $this->command->info('Created ' . count($transactions) . ' transactions');
    }
}
