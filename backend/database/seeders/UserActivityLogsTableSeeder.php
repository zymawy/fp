<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class UserActivityLogsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get all user IDs
        $userIds = DB::table('users')->pluck('id')->toArray();
        
        // Get donation and cause IDs to reference in some logs
        $donationIds = DB::table('donations')->pluck('id')->toArray();
        $causeIds = DB::table('causes')->pluck('id')->toArray();
        
        $logs = [];
        $activityTypes = [
            'login' => ['Logged in to account', 'Successfully authenticated', 'New login from desktop'],
            'donation' => ['Made a donation', 'Supported a cause', 'Contributed to a fundraiser'],
            'profile' => ['Updated profile information', 'Changed password', 'Updated contact details'],
            'browse' => ['Browsed causes', 'Viewed cause details', 'Searched for causes'],
            'share' => ['Shared a cause on social media', 'Invited a friend to donate', 'Promoted a cause']
        ];
        
        // Create 100 activity logs
        for ($i = 0; $i < 100; $i++) {
            $userId = $faker->randomElement($userIds);
            $activityType = $faker->randomElement(array_keys($activityTypes));
            $description = $faker->randomElement($activityTypes[$activityType]);
            
            $logs[] = [
                'id' => Str::uuid()->toString(),
                'user_id' => $userId,
                'activity_type' => $activityType,
                'description' => $description,
                'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => now(),
            ];
        }
        
        // Chunk insert to avoid memory issues
        foreach (array_chunk($logs, 20) as $chunk) {
            DB::table('user_activity_logs')->insert($chunk);
        }
        
        $this->command->info('Created 100 user activity logs');
    }
}
