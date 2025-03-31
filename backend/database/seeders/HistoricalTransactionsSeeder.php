<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;

class HistoricalTransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get donation IDs from past years (historical data)
        $startYear = 2020;
        $currentYear = date('Y');
        
        $donationIds = DB::table('donations')
            ->whereYear('created_at', '>=', $startYear)
            ->whereYear('created_at', '<=', $currentYear)
            ->pluck('id')
            ->toArray();
        
        if (empty($donationIds)) {
            $this->command->error('No historical donations found. Please run the HistoricalDataSeeder first.');
            return;
        }
        
        $this->command->info('Found ' . count($donationIds) . ' historical donations to process');
        
        $transactions = [];
        $count = 0;
        
        // Payment methods
        $paymentMethods = ['stripe', 'paypal', 'bank_transfer'];
        
        // Payment statuses
        $paymentStatuses = ['completed', 'pending', 'failed', 'refunded'];
        
        // Get donation details
        $donations = DB::table('donations')
            ->whereIn('id', $donationIds)
            ->get(['id', 'payment_status', 'created_at']);
        
        foreach ($donations as $donation) {
            // Only create transactions for completed or refunded donations
            if ($donation->payment_status !== 'completed' && $donation->payment_status !== 'refunded') {
                continue;
            }
            
            $transaction = [
                'id' => Str::uuid()->toString(),
                'donation_id' => $donation->id,
                'payment_method' => $faker->randomElement($paymentMethods),
                'payment_status' => 'completed',
                'transaction_id' => $faker->bothify('txn_???????????'),
                'timestamp' => $donation->created_at,
                'created_at' => $donation->created_at,
                'updated_at' => $donation->created_at,
                'payment_data' => json_encode([
                    'provider' => $faker->randomElement(['stripe', 'paypal']),
                    'transaction_time' => Carbon::parse($donation->created_at)->format('c'),
                    'status_message' => 'Payment processed successfully'
                ])
            ];
            
            $transactions[] = $transaction;
            $count++;
            
            // Add occasional chargebacks (about 3%)
            if ($faker->boolean(3)) {
                $chargebackDate = Carbon::parse($donation->created_at)
                    ->addDays($faker->numberBetween(3, 30))
                    ->format('Y-m-d H:i:s');
                
                $transactions[] = [
                    'id' => Str::uuid()->toString(),
                    'donation_id' => $donation->id,
                    'payment_method' => $faker->randomElement($paymentMethods),
                    'payment_status' => 'refunded',
                    'transaction_id' => $faker->bothify('txn_???????????'),
                    'timestamp' => $chargebackDate,
                    'created_at' => $chargebackDate,
                    'updated_at' => $chargebackDate,
                    'payment_data' => json_encode([
                        'provider' => $faker->randomElement(['stripe', 'paypal']),
                        'transaction_time' => Carbon::parse($chargebackDate)->format('c'),
                        'status_message' => 'Payment refunded due to chargeback',
                        'reason' => $faker->randomElement(['customer_request', 'fraud', 'duplicate', 'expired_card'])
                    ])
                ];
                
                $count++;
            }
            
            // Insert in chunks to avoid memory issues
            if (count($transactions) >= 100) {
                DB::table('transactions')->insert($transactions);
                $transactions = [];
            }
        }
        
        // Insert any remaining transactions
        if (!empty($transactions)) {
            DB::table('transactions')->insert($transactions);
        }
        
        $this->command->info("Created $count historical transactions");
    }
} 