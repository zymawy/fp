<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class CauseReportsTableSeeder extends Seeder
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
        
        // Generate 30 reports (not all causes will have reports)
        for ($i = 0; $i < 30; $i++) {
            $causeId = $faker->randomElement($causeIds);
            $cause = DB::table('causes')->where('id', $causeId)->first();
            
            if (!$cause) continue;
            
            // Create report date within the last year
            $reportDate = $faker->dateTimeBetween('-1 year', 'now');
            
            // Calculate some fake donation data
            $totalDonations = $faker->randomFloat(2, 500, 10000);
            
            $reports[] = [
                'id' => Str::uuid()->toString(),
                'cause_id' => $causeId,
                'total_donations' => $totalDonations,
                'created_at' => $reportDate,
                'updated_at' => $reportDate,
            ];
        }
        
        DB::table('cause_reports')->insert($reports);
        $this->command->info('Created ' . count($reports) . ' cause reports');
    }
}
