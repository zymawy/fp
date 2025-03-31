<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;

class HistoricalDataSeeder extends Seeder
{
    /**
     * Real images to use for causes
     */
    protected $realImages = [
        'https://ehsanimagesp.s3.me-south-1.amazonaws.com/P116.jpg',
        'https://ehsanimagesp.s3.me-south-1.amazonaws.com/P25700.png',
        'https://ehsanimagesp.s3.me-south-1.amazonaws.com/P02245.jpg',
        'https://ehsanimagesp.s3.me-south-1.amazonaws.com/P01944.jpg',
        'https://ehsanimagesr.s3.me-south-1.amazonaws.com/R01048.jpg',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get category IDs by name
        $categories = DB::table('categories')->get(['id', 'name'])->keyBy('name');
        
        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Please run the CategoriesTableSeeder first.');
            return;
        }
        
        // Get user IDs (preferably donors)
        $donorIds = DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.role_name', 'Donor')
            ->pluck('users.id')
            ->toArray();
            
        // Fallback to all users if no donors found
        if (empty($donorIds)) {
            $donorIds = DB::table('users')->pluck('id')->toArray();
        }
        
        if (empty($donorIds)) {
            $this->command->error('No users found. Please run the UsersTableSeeder first.');
            return;
        }
        
        // Get payment statuses
        $paymentStatuses = ['completed', 'pending', 'failed', 'refunded'];
        
        // Currency codes
        $currencyCodes = ['USD', 'EUR', 'GBP'];
        
        // Check if the 'raised_amount' column exists in the causes table
        $hasRaisedAmountColumn = Schema::hasColumn('causes', 'raised_amount');
        
        // Years to generate historical data for
        $years = [2020, 2021, 2022, 2023, 2024];
        
        // Category names
        $categoryNames = ['Education', 'Healthcare', 'Disaster Relief', 'Poverty', 'Environment'];
        
        $causesData = [];
        $causesIds = [];
        $donationsData = [];
        $donationsIds = [];
        
        $this->command->info('Generating historical data for years: ' . implode(', ', $years));
        
        // For each year, create causes and donations
        foreach ($years as $year) {
            $this->command->info("Generating data for year $year");
            
            // Create causes for each category for this year
            foreach ($categoryNames as $categoryIndex => $categoryName) {
                if (!isset($categories[$categoryName])) {
                    $this->command->warn("Category '$categoryName' not found, skipping.");
                    continue;
                }
                
                $categoryId = $categories[$categoryName]->id;
                
                // Create 2 causes per category per year
                for ($i = 0; $i < 2; $i++) {
                    $causeTitle = $this->generateCauseTitle($faker, $categoryName, $year);
                    $startDate = Carbon::createFromDate($year, $faker->numberBetween(1, 6), $faker->numberBetween(1, 28));
                    $endDate = Carbon::createFromDate($year, $faker->numberBetween(7, 12), $faker->numberBetween(1, 28));
                    $createdAt = Carbon::createFromDate($year, $faker->numberBetween(1, 3), $faker->numberBetween(1, 28));
                    
                    // Select an image from the real images array
                    $imageIndex = ($year - 2020) % count($this->realImages);
                    $mediaUrl = $this->realImages[($imageIndex + $i) % count($this->realImages)];
                    
                    $goalAmount = $faker->randomFloat(2, 10000, 100000);
                    
                    $causeId = Str::uuid()->toString();
                    $causesIds[] = $causeId;
                    
                    $causesData[] = [
                        'id' => $causeId,
                        'title' => $causeTitle,
                        'description' => $faker->paragraph(5),
                        'category_id' => $categoryId,
                        'goal_amount' => $goalAmount,
                        'raised_amount' => 0, // Will be updated as donations are created
                        'media_url' => $mediaUrl,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                        'is_featured' => $faker->boolean(20), // 20% chance of being featured
                        'status' => $year < 2024 ? 'completed' : $faker->randomElement(['active', 'completed', 'pending']),
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'slug' => Str::slug($causeTitle),
                    ];
                    
                    // Generate between 20 and 40 donations for each cause
                    $donationsCount = $faker->numberBetween(20, 40);
                    
                    // Generate donations spread across the cause timeline
                    for ($j = 0; $j < $donationsCount; $j++) {
                        $amount = $faker->randomFloat(2, 10, 1000);
                        $processingFee = round($amount * 0.029 + 0.30, 2); // Typical payment processing fee
                        $coverFees = $faker->boolean(70); // 70% chance to cover fees
                        $isAnonymous = $faker->boolean(30); // 30% chance to be anonymous
                        $isGift = $faker->boolean(20); // 20% chance to be a gift
                        
                        $totalAmount = $coverFees ? $amount + $processingFee : $amount;
                        $paymentStatus = $faker->randomElement($paymentStatuses);
                        
                        // Make more donations have 'completed' status for historical data
                        if ($year < 2024) {
                            $paymentStatus = $faker->boolean(90) ? 'completed' : $paymentStatus;
                        }
                        
                        // Generate a date between the start and end date of the cause
                        $donationDate = $faker->dateTimeBetween($startDate, $endDate < now() ? $endDate : now());
                        
                        $donationId = Str::uuid()->toString();
                        $donationsIds[] = $donationId;
                        
                        $donation = [
                            'id' => $donationId,
                            'user_id' => $faker->randomElement($donorIds),
                            'cause_id' => $causeId,
                            'amount' => $amount,
                            'total_amount' => $totalAmount,
                            'processing_fee' => $processingFee,
                            'is_anonymous' => $isAnonymous,
                            'cover_fees' => $coverFees,
                            'currency_code' => $faker->randomElement($currencyCodes),
                            'payment_status' => $paymentStatus,
                            'payment_method_id' => $faker->bothify('pm_????_????????'),
                            'payment_id' => $faker->bothify('pi_????_????????'),
                            'created_at' => $donationDate,
                            'updated_at' => $donationDate,
                        ];
                        
                        // Add gift details if it's a gift
                        if ($isGift) {
                            $donation['is_gift'] = true;
                            $donation['gift_message'] = $faker->sentence(10);
                            $donation['recipient_name'] = $faker->name();
                            $donation['recipient_email'] = $faker->email();
                        } else {
                            $donation['is_gift'] = false;
                            $donation['gift_message'] = null;
                            $donation['recipient_name'] = null;
                            $donation['recipient_email'] = null;
                        }
                        
                        $donationsData[] = $donation;
                        
                        // Update the cause's raised_amount for completed donations
                        if ($paymentStatus === 'completed') {
                            foreach ($causesData as &$cause) {
                                if ($cause['id'] === $causeId) {
                                    $cause['raised_amount'] += $amount;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Insert all causes
        $this->command->info('Inserting ' . count($causesData) . ' historical causes into the database...');
        foreach (array_chunk($causesData, 50) as $causeChunk) {
            DB::table('causes')->insert($causeChunk);
        }
        
        // Insert all donations
        $this->command->info('Inserting ' . count($donationsData) . ' historical donations into the database...');
        foreach (array_chunk($donationsData, 100) as $donationChunk) {
            DB::table('donations')->insert($donationChunk);
        }
        
        // Store donation IDs for transactions seeder
        app()->instance('historical_donation_ids', $donationsIds);
        $this->command->info('Historical data seeding complete!');
    }
    
    /**
     * Generate a cause title relevant to the category and year
     */
    protected function generateCauseTitle($faker, $category, $year)
    {
        $titles = [
            'Education' => [
                "School Expansion Project $year",
                "Rural Education Access $year",
                "Teacher Training Initiative $year",
                "Digital Learning for All $year",
                "Scholarship Fund $year",
                "Library Restoration $year",
                "STEM Education Program $year",
                "School Supplies Drive $year"
            ],
            'Healthcare' => [
                "Medical Outreach Program $year",
                "Rural Health Clinic $year",
                "Children's Hospital Support $year",
                "Medical Equipment Fund $year",
                "Vaccination Campaign $year",
                "Mental Health Services $year",
                "Women's Health Initiative $year",
                "Elder Care Support $year"
            ],
            'Disaster Relief' => [
                "Flood Recovery Fund $year",
                "Hurricane Rebuilding Project $year",
                "Earthquake Response Initiative $year",
                "Wildfire Relief Efforts $year",
                "Drought Emergency Response $year",
                "Disaster Preparedness Program $year",
                "Emergency Shelter Project $year",
                "Refugee Support Program $year"
            ],
            'Poverty' => [
                "Food Bank Expansion $year",
                "Affordable Housing Project $year",
                "Job Training Initiative $year",
                "Homelessness Prevention $year",
                "Winter Relief Program $year",
                "Children's Meal Program $year",
                "Community Empowerment Project $year",
                "Financial Literacy Program $year"
            ],
            'Environment' => [
                "Reforestation Initiative $year",
                "Ocean Cleanup Campaign $year",
                "Renewable Energy Project $year",
                "Wildlife Conservation $year",
                "Sustainable Agriculture $year",
                "Clean Water Initiative $year",
                "Plastic Reduction Program $year",
                "Green Community Development $year"
            ]
        ];
        
        // Default to first category if the one provided doesn't exist in our array
        if (!isset($titles[$category])) {
            $category = array_key_first($titles);
        }
        
        // Select a random title from the category and append a location
        return $faker->randomElement($titles[$category]) . ' in ' . $faker->city;
    }
} 