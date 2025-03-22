<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class CausesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get category IDs by name
        $categories = DB::table('categories')->get(['id', 'name'])->keyBy('name');
        
        $causes = [];

        // Specific image URLs for each category
        $educationImages = [
            'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1000',
            'https://images.unsplash.com/photo-1577896851231-70ef18881754?q=80&w=1000',
            'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?q=80&w=1000',
            'https://images.unsplash.com/photo-1504805572947-34fad45aed93?q=80&w=1000',
            'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1000',
        ];
        
        $healthcareImages = [
            'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?q=80&w=1000',
            'https://images.unsplash.com/photo-1587377838789-968194a7cc88?q=80&w=1000',
            'https://images.unsplash.com/photo-1584982751601-97dcc096659c?q=80&w=1000',
            'https://images.unsplash.com/photo-1579684385127-1ef15d508118?q=80&w=1000',
            'https://images.unsplash.com/photo-1576091160550-2173dba999ef?q=80&w=1000',
        ];
        
        $disasterImages = [
            'https://images.unsplash.com/photo-1589439069158-64c438abe4ec?q=80&w=1000',
            'https://images.unsplash.com/photo-1598967616730-fb8a0a97d7ba?q=80&w=1000',
            'https://images.unsplash.com/photo-1623840598683-943820ff1c4d?q=80&w=1000',
            'https://images.unsplash.com/photo-1556768583-1c18a1967027?q=80&w=1000',
            'https://images.unsplash.com/photo-1583672612397-f7efc604ec22?q=80&w=1000',
        ];
        
        $povertyImages = [
            'https://images.unsplash.com/photo-1532629345422-7515f3d16bb6?q=80&w=1000',
            'https://images.unsplash.com/photo-1541802645635-11f2286a7482?q=80&w=1000',
            'https://images.unsplash.com/photo-1603398938378-e54eab446dde?q=80&w=1000',
            'https://images.unsplash.com/photo-1518398046578-8cca57782e17?q=80&w=1000',
            'https://images.unsplash.com/photo-1444664597500-983c957faafa?q=80&w=1000',
        ];
        
        $environmentImages = [
            'https://images.unsplash.com/photo-1541675154750-0444c7d51e8e?q=80&w=1000',
            'https://images.unsplash.com/photo-1516214104703-d870798883c5?q=80&w=1000',
            'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?q=80&w=1000',
            'https://images.unsplash.com/photo-1498429089284-41f8cf3ffd39?q=80&w=1000',
            'https://images.unsplash.com/photo-1472145246862-b24cf25c4a36?q=80&w=1000',
        ];
        
        // Education causes
        for ($i = 0; $i < 5; $i++) {
            $causes[] = [
                'id' => Str::uuid()->toString(),
                'title' => $faker->randomElement([
                    'Education for Underprivileged Children',
                    'School Building Project',
                    'College Scholarship Program',
                    'Books for Rural Schools',
                    'Teacher Training Initiative',
                    'Girls Education Support',
                    'Technology for Classrooms',
                    'Literacy Campaign'
                ]) . ' ' . $faker->city,
                'description' => $faker->paragraph(3),
                'category_id' => $categories['Education']->id,
                'goal_amount' => $faker->randomFloat(2, 10000, 100000),
                'raised_amount' => $faker->randomFloat(2, 0, 50000),
                'media_url' => $educationImages[$i],
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
                'is_featured' => $faker->boolean(20), // 20% chance of being featured
                'status' => $faker->randomElement(['active', 'completed', 'pending']),
                'start_date' => $faker->dateTimeBetween('-1 year', '-1 month'),
                'end_date' => $faker->dateTimeBetween('+1 month', '+1 year'),
                'slug' => Str::slug($faker->sentence(3)),
            ];
        }
        
        // Healthcare causes
        for ($i = 0; $i < 5; $i++) {
            $causes[] = [
                'id' => Str::uuid()->toString(),
                'title' => $faker->randomElement([
                    'Medical Supplies for Rural Clinics',
                    'Children\'s Hospital Equipment',
                    'Healthcare Access Program',
                    'Maternity Care Initiative',
                    'Cancer Treatment Support',
                    'Mental Health Services',
                    'Mobile Medical Clinic',
                    'Vaccination Drive'
                ]) . ' ' . $faker->city,
                'description' => $faker->paragraph(3),
                'category_id' => $categories['Healthcare']->id,
                'goal_amount' => $faker->randomFloat(2, 20000, 150000),
                'raised_amount' => $faker->randomFloat(2, 5000, 70000),
                'media_url' => $healthcareImages[$i],
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
                'is_featured' => $faker->boolean(20), // 20% chance of being featured
                'status' => $faker->randomElement(['active', 'completed', 'pending']),
                'start_date' => $faker->dateTimeBetween('-1 year', '-1 month'),
                'end_date' => $faker->dateTimeBetween('+1 month', '+1 year'),
                'slug' => Str::slug($faker->sentence(3)),
            ];
        }
        
        // Disaster Relief causes
        for ($i = 0; $i < 5; $i++) {
            $causes[] = [
                'id' => Str::uuid()->toString(),
                'title' => $faker->randomElement([
                    'Earthquake Relief Fund',
                    'Flood Recovery Program',
                    'Wildfire Victim Support',
                    'Hurricane Relief Effort',
                    'Tsunami Recovery',
                    'Emergency Shelter Initiative',
                    'Drought Relief Program',
                    'Disaster Preparedness Training'
                ]) . ' ' . $faker->city,
                'description' => $faker->paragraph(3),
                'category_id' => $categories['Disaster Relief']->id,
                'goal_amount' => $faker->randomFloat(2, 50000, 200000),
                'raised_amount' => $faker->randomFloat(2, 10000, 100000),
                'media_url' => $disasterImages[$i],
                'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => now(),
                'is_featured' => $faker->boolean(20), // 20% chance of being featured
                'status' => $faker->randomElement(['active', 'completed', 'pending']),
                'start_date' => $faker->dateTimeBetween('-6 months', '-1 month'),
                'end_date' => $faker->dateTimeBetween('+1 month', '+1 year'),
                'slug' => Str::slug($faker->sentence(3)),
            ];
        }
        
        // Poverty causes
        for ($i = 0; $i < 5; $i++) {
            $causes[] = [
                'id' => Str::uuid()->toString(),
                'title' => $faker->randomElement([
                    'Food Bank Program',
                    'Homelessness Support Initiative',
                    'Housing for Low-Income Families',
                    'Job Training for Disadvantaged',
                    'Anti-Poverty Campaign',
                    'Community Kitchen Project',
                    'Winter Shelter Program',
                    'Child Hunger Alleviation'
                ]) . ' ' . $faker->city,
                'description' => $faker->paragraph(3),
                'category_id' => $categories['Poverty']->id,
                'goal_amount' => $faker->randomFloat(2, 15000, 80000),
                'raised_amount' => $faker->randomFloat(2, 2000, 40000),
                'media_url' => $povertyImages[$i],
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
                'is_featured' => $faker->boolean(20), // 20% chance of being featured
                'status' => $faker->randomElement(['active', 'completed', 'pending']),
                'start_date' => $faker->dateTimeBetween('-1 year', '-1 month'),
                'end_date' => $faker->dateTimeBetween('+1 month', '+1 year'),
                'slug' => Str::slug($faker->sentence(3)),
            ];
        }
        
        // Environment causes
        for ($i = 0; $i < 5; $i++) {
            $causes[] = [
                'id' => Str::uuid()->toString(),
                'title' => $faker->randomElement([
                    'Clean Water Initiative',
                    'Reforestation Project',
                    'Ocean Cleanup Campaign',
                    'Renewable Energy Access',
                    'Wildlife Conservation',
                    'Climate Action Program',
                    'Sustainable Agriculture',
                    'Plastic Reduction Initiative'
                ]) . ' ' . $faker->city,
                'description' => $faker->paragraph(3),
                'category_id' => $categories['Environment']->id,
                'goal_amount' => $faker->randomFloat(2, 25000, 120000),
                'raised_amount' => $faker->randomFloat(2, 5000, 60000),
                'media_url' => $environmentImages[$i],
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
                'is_featured' => $faker->boolean(20), // 20% chance of being featured
                'status' => $faker->randomElement(['active', 'completed', 'pending']),
                'start_date' => $faker->dateTimeBetween('-1 year', '-1 month'),
                'end_date' => $faker->dateTimeBetween('+1 month', '+1 year'),
                'slug' => Str::slug($faker->sentence(3)),
            ];
        }

        DB::table('causes')->insert($causes);
        
        // Store cause IDs for other seeders
        $causeIds = DB::table('causes')->pluck('id')->toArray();
        app()->instance('seeded_cause_ids', $causeIds);
        $this->command->info('Cause IDs stored for other seeders');
    }
}
