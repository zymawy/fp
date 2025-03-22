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
        $achievementTypes = [
            [
                'id' => Str::uuid()->toString(),
                'title' => 'First Donation',
                'description' => 'Made your first donation to a cause.',
                'icon' => 'fa-hand-holding-heart',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Generous Donor',
                'description' => 'Donated more than $500 in total.',
                'icon' => 'fa-money-bill-wave',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Regular Supporter',
                'description' => 'Made donations in three consecutive months.',
                'icon' => 'fa-calendar-check',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Cause Champion',
                'description' => 'Donated to the same cause five times.',
                'icon' => 'fa-trophy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Diversity Supporter',
                'description' => 'Donated to causes in five different categories.',
                'icon' => 'fa-globe',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Profile Completer',
                'description' => 'Completed 100% of your profile information.',
                'icon' => 'fa-user-check',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Social Sharer',
                'description' => 'Shared a cause on social media.',
                'icon' => 'fa-share-alt',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Milestone Donor',
                'description' => 'Made your 10th donation.',
                'icon' => 'fa-flag-checkered',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Yearly Commitment',
                'description' => 'Donated every month for a full year.',
                'icon' => 'fa-award',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Impact Multiplier',
                'description' => 'Referred five friends who made donations.',
                'icon' => 'fa-users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        DB::table('achievement_types')->insert($achievementTypes);
        
        $this->command->info('Created ' . count($achievementTypes) . ' achievement types');
    }
} 