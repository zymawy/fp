<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class AchievementsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get donor user IDs
        $donorIds = DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.role_name', 'Donor')
            ->pluck('users.id')
            ->toArray();
            
        // If no donors found, use any user
        if (empty($donorIds)) {
            $donorIds = DB::table('users')->pluck('id')->toArray();
        }
        
        // Get achievement type IDs
        $achievementTypeIds = DB::table('achievement_types')->pluck('id')->toArray();
        
        $achievements = [];
        
        // Create achievements for users - approximately 50 achievements
        foreach ($donorIds as $userId) {
            // Each user gets between 0 and 5 achievements
            $numAchievements = $faker->numberBetween(0, 5);
            
            // Randomly select achievement types for this user (no duplicates)
            $selectedTypes = $faker->randomElements(
                $achievementTypeIds, 
                min($numAchievements, count($achievementTypeIds))
            );
            
            foreach ($selectedTypes as $typeId) {
                $achievements[] = [
                    'id' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'achievement_type_id' => $typeId,
                    'achieved_at' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
                ];
            }
        }
        
        if (!empty($achievements)) {
            DB::table('achievements')->insert($achievements);
            $this->command->info('Created ' . count($achievements) . ' achievements');
        } else {
            $this->command->info('No achievements created - check that users and achievement types exist');
        }
    }
} 