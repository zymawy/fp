<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Cause;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CauseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure we have categories first
        if (Category::count() === 0) {
            $this->createCategories();
        }
        
        $categories = Category::all();
        
        // Create causes
        $causesData = [
            [
                'title' => 'Clean Water Initiative',
                'description' => 'Provide clean drinking water to villages in developing countries by installing water filtration systems.',
                'goal_amount' => 50000.00,
                'raised_amount' => 10000.00,
                'status' => 'active',
                'is_featured' => true,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'image' => 'https://source.unsplash.com/random/800x600/?water',
            ],
            [
                'title' => 'Education for All',
                'description' => 'Build schools and provide education resources to underprivileged communities.',
                'goal_amount' => 75000.00,
                'raised_amount' => 25000.00,
                'status' => 'active',
                'is_featured' => true,
                'start_date' => now()->subDays(30),
                'end_date' => now()->addMonths(12),
                'image' => 'https://source.unsplash.com/random/800x600/?education',
            ],
            [
                'title' => 'Wildlife Conservation Project',
                'description' => 'Protect endangered species and preserve natural habitats.',
                'goal_amount' => 100000.00,
                'raised_amount' => 40000.00,
                'status' => 'active',
                'is_featured' => false,
                'start_date' => now()->subDays(60),
                'end_date' => now()->addMonths(18),
                'image' => 'https://source.unsplash.com/random/800x600/?wildlife',
            ],
            [
                'title' => 'Emergency Medical Relief',
                'description' => 'Provide emergency medical care and supplies to disaster-affected areas.',
                'goal_amount' => 120000.00,
                'raised_amount' => 60000.00,
                'status' => 'active',
                'is_featured' => true,
                'start_date' => now()->subDays(15),
                'end_date' => now()->addMonths(3),
                'image' => 'https://source.unsplash.com/random/800x600/?medical',
            ],
            [
                'title' => 'Hunger Relief Program',
                'description' => 'Distribute food and nutrition supplies to areas suffering from food insecurity.',
                'goal_amount' => 80000.00,
                'raised_amount' => 15000.00,
                'status' => 'active',
                'is_featured' => false,
                'start_date' => now()->subDays(45),
                'end_date' => now()->addMonths(9),
                'image' => 'https://source.unsplash.com/random/800x600/?food',
            ],
        ];
        
        foreach ($causesData as $causeData) {
            $categoryId = $categories->random()->id;
            
            Cause::create([
                'title' => $causeData['title'],
                'slug' => Str::slug($causeData['title']),
                'description' => $causeData['description'],
                'goal_amount' => $causeData['goal_amount'],
                'raised_amount' => $causeData['raised_amount'],
                'status' => $causeData['status'],
                'is_featured' => $causeData['is_featured'],
                'start_date' => $causeData['start_date'],
                'end_date' => $causeData['end_date'],
                'image' => $causeData['image'],
                'category_id' => $categoryId,
            ]);
        }
    }
    
    /**
     * Create sample categories
     */
    private function createCategories(): void
    {
        $categories = [
            'Education',
            'Healthcare',
            'Environment',
            'Poverty Relief',
            'Disaster Response',
            'Animal Welfare',
        ];
        
        foreach ($categories as $categoryName) {
            Category::create([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName),
            ]);
        }
    }
}
