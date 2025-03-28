<?php

namespace Database\Seeders;

use App\Models\AchievementType;
use Illuminate\Database\Seeder;

class AchievementTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'title' => 'First Donation',
                'description' => 'Made your first donation',
                'icon' => 'heart',
            ],
            [
                'title' => 'Generous Donor',
                'description' => 'Donated more than $1000 in total',
                'icon' => 'star',
            ],
            [
                'title' => 'Regular Supporter',
                'description' => 'Made donations in 3 consecutive months',
                'icon' => 'award',
            ],
        ];

        foreach ($types as $type) {
            AchievementType::create($type);
        }
    }
} 