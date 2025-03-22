<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Cause;

class DonationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get user IDs (preferably donors)
        $donorIds = DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.role_name', 'Donor')
            ->pluck('users.id')
            ->toArray();
            
        // Fallback to all users if no donors found
        if (empty($donorIds)) {
            $donorIds = DB::table('users')->pluck('id')->toArray();
        }
        
        // Get cause IDs
        $causeIds = DB::table('causes')->pluck('id')->toArray();
        
        if (empty($causeIds)) {
            $this->command->error('No causes found. Please run the CausesTableSeeder first.');
            return;
        }
        
        if (empty($donorIds)) {
            $this->command->error('No users found. Please run the UsersTableSeeder first.');
            return;
        }
        
        $donations = [];
        $donationIds = [];
        
        // Payment statuses
        $paymentStatuses = ['completed', 'pending', 'failed', 'refunded'];
        
        // Currency codes
        $currencyCodes = ['USD', 'EUR', 'GBP'];
        
        // Check if the 'raised_amount' column exists in the causes table
        $hasRaisedAmountColumn = Schema::hasColumn('causes', 'raised_amount');
        if (!$hasRaisedAmountColumn) {
            $this->command->info('Note: raised_amount column not found in causes table, will not update cause amounts.');
        }
        
        // Create 50 donations
        for ($i = 0; $i < 50; $i++) {
            $amount = $faker->randomFloat(2, 10, 1000);
            $processingFee = round($amount * 0.029 + 0.30, 2); // Typical payment processing fee
            $coverFees = $faker->boolean(70); // 70% chance to cover fees
            $isAnonymous = $faker->boolean(30); // 30% chance to be anonymous
            $isGift = $faker->boolean(20); // 20% chance to be a gift
            
            $totalAmount = $coverFees ? $amount + $processingFee : $amount;
            $paymentStatus = $faker->randomElement($paymentStatuses);
            $causeId = $faker->randomElement($causeIds);
            $donationId = Str::uuid()->toString();
            
            $donation = [
                'id' => $donationId,
                'user_id' => $faker->randomElement($donorIds),
                'cause_id' => $causeId,
                'amount' => $amount,
                'total_amount' => $totalAmount,
                'processing_fee' => $processingFee,
                'is_anonymous' => $isAnonymous,
                'cover_fees' => $coverFees,
                'currency_code' => $faker->randomElement($currencyCodes),
                'payment_status' => $paymentStatus,
                'payment_method_id' => $faker->bothify('pm_????_????????'),
                'payment_id' => $faker->bothify('pi_????_????????'),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ];
            
            // Add gift details if it's a gift
            if ($isGift) {
                $donation['is_gift'] = true;
                $donation['gift_message'] = $faker->sentence(10);
                $donation['recipient_name'] = $faker->name();
                $donation['recipient_email'] = $faker->email();
            } else {
                $donation['is_gift'] = false;
                $donation['gift_message'] = null;
                $donation['recipient_name'] = null;
                $donation['recipient_email'] = null;
            }
            
            $donations[] = $donation;
            $donationIds[] = $donationId;
            
            // Update cause's raised_amount if payment is completed and column exists
            if ($paymentStatus === 'completed' && $hasRaisedAmountColumn) {
                DB::table('causes')
                    ->where('id', $causeId)
                    ->increment('raised_amount', $amount);
            }
        }
        
        DB::table('donations')->insert($donations);
        
        // Store donation IDs for transactions seeder
        app()->instance('seeded_donation_ids', $donationIds);
        $this->command->info('Created 50 donations and stored IDs for other seeders');
    }
}
