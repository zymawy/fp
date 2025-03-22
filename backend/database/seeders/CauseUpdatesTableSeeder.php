<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class CauseUpdatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get cause IDs
        $causeIds = DB::table('causes')->pluck('id')->toArray();
        
        if (empty($causeIds)) {
            $this->command->info('No causes found to create updates for');
            return;
        }
        
        $updates = [];
        
        // Create approximately 50 updates
        // Each cause will have 0-3 updates
        foreach ($causeIds as $causeId) {
            $numUpdates = $faker->numberBetween(0, 3);
            
            for ($i = 0; $i < $numUpdates; $i++) {
                $createdAt = $faker->dateTimeBetween('-6 months', 'now');
                
                $updates[] = [
                    'id' => Str::uuid()->toString(),
                    'cause_id' => $causeId,
                    'title' => $faker->sentence(6, true),
                    'content' => $faker->paragraphs(3, true),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }
        
        if (!empty($updates)) {
            DB::table('cause_updates')->insert($updates);
            $this->command->info('Created ' . count($updates) . ' cause updates');
        } else {
            $this->command->info('No cause updates created');
        }
    }
} 