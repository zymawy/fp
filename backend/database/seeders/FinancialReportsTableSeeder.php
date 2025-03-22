<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class FinancialReportsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get cause IDs
        $causeIds = DB::table('causes')->pluck('id')->toArray();
        
        $reports = [];
        
        // Create quarterly reports for past 2 years
        $periods = [
            'Q1' => 'January - March',
            'Q2' => 'April - June',
            'Q3' => 'July - September',
            'Q4' => 'October - December',
            'Annual' => 'Full Year'
        ];
        
        $currentYear = date('Y');
        
        // Create approximately 30 reports
        for ($year = $currentYear - 2; $year <= $currentYear; $year++) {
            foreach ($periods as $period => $description) {
                // Only create reports for past periods
                $reportDate = new \DateTime();
                
                if ($period === 'Annual') {
                    $reportDate->setDate($year, 12, 31);
                } else if ($period === 'Q1') {
                    $reportDate->setDate($year, 3, 31);
                } else if ($period === 'Q2') {
                    $reportDate->setDate($year, 6, 30);
                } else if ($period === 'Q3') {
                    $reportDate->setDate($year, 9, 30);
                } else if ($period === 'Q4') {
                    $reportDate->setDate($year, 12, 31);
                }
                
                // Skip future periods
                if ($reportDate > new \DateTime()) {
                    continue;
                }
                
                // Assign a random cause to the report
                $causeId = $faker->randomElement($causeIds);
                
                // Generate financial data
                $totalDonations = $faker->randomFloat(2, 5000, 100000);
                $totalExpenditure = $faker->randomFloat(2, 1000, $totalDonations * 0.8);
                
                $reports[] = [
                    'id' => Str::uuid()->toString(),
                    'period' => "$period $year",
                    'total_donations' => $totalDonations,
                    'total_expenditure' => $totalExpenditure,
                    'created_at' => $reportDate->format('Y-m-d H:i:s'),
                    'updated_at' => $reportDate->format('Y-m-d H:i:s'),
                    'deleted_at' => null,
                    'cause_id' => $causeId
                ];
            }
        }
        
        DB::table('financial_reports')->insert($reports);
        $this->command->info('Created ' . count($reports) . ' financial reports');
    }
}
