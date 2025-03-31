<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;

class HistoricalFinancialReportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Years to generate reports for
        $years = [2020, 2021, 2022, 2023, 2024];
        
        // Get cause IDs
        $causeIds = DB::table('causes')->pluck('id')->toArray();
        
        if (empty($causeIds)) {
            $this->command->error('No causes found. Please run the CausesTableSeeder first.');
            return;
        }
        
        $reports = [];
        
        $this->command->info('Generating quarterly financial reports for years: ' . implode(', ', $years));
        
        foreach ($years as $year) {
            // For each quarter of the year
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                // Skip future quarters for current year
                if ($year == date('Y') && $quarter > ceil(date('n') / 3)) {
                    continue;
                }
                
                // Determine quarter start and end dates
                $startMonth = (($quarter - 1) * 3) + 1;
                $endMonth = $startMonth + 2;
                
                $startDate = Carbon::createFromDate($year, $startMonth, 1);
                $endDate = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth();
                
                // Report creation date is typically after the quarter ends
                $createdAt = $endDate->copy()->addDays($faker->numberBetween(10, 20));
                
                // Get actual donation totals for this period (if possible)
                $donationTotal = DB::table('donations')
                    ->where('payment_status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount');
                
                // Fallback to randomized numbers if no real data
                if (!$donationTotal) {
                    $donationTotal = $faker->randomFloat(2, 25000, 150000);
                }
                
                // Calculate total expenditure (between 60-90% of donations)
                $totalExpenditure = $donationTotal * $faker->randomFloat(2, 0.60, 0.90);
                
                // Assign to a random cause from that period if possible
                $periodCauses = DB::table('causes')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->pluck('id')
                    ->toArray();
                
                $causeId = !empty($periodCauses) 
                    ? $faker->randomElement($periodCauses) 
                    : $faker->randomElement($causeIds);
                
                // Create the report
                $reports[] = [
                    'id' => Str::uuid()->toString(),
                    'period' => "Q$quarter $year",
                    'total_donations' => $donationTotal,
                    'total_expenditure' => $totalExpenditure,
                    'cause_id' => $causeId,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }
        
        // Insert all reports
        DB::table('financial_reports')->insert($reports);
        
        $this->command->info('Generated ' . count($reports) . ' historical financial reports');
    }
} 