<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AchievementTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if achievement types already exist
        if (DB::table('achievement_types')->count() > 0) {
            $this->command->info('Achievement types already exist. Skipping...');
            return;
        }

        $achievementTypes = [
            [
                'id' => '0195d229-9a72-736f-b605-9005560529eb',
                'title' => 'First Donation',
                'description' => 'Made your first donation',
                'icon' => 'heart',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '0195d229-9a8c-738b-a64a-dbbbaa10864e',
                'title' => 'Generous Donor',
                'description' => 'Donated more than $1000 in total',
                'icon' => 'star',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '0195d229-9a8f-72ed-83cc-2bc762144cab',
                'title' => 'Regular Supporter',
                'description' => 'Made donations in 3 consecutive months',
                'icon' => 'award',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        DB::table('achievement_types')->insert($achievementTypes);
        
        $this->command->info('Created ' . count($achievementTypes) . ' achievement types');
    }
} 