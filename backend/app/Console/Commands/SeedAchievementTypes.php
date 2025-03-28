<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\AchievementTypesTableSeeder;

class SeedAchievementTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-achievement-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the achievement types for the donation system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding achievement types...');
        
        $seeder = new AchievementTypesTableSeeder();
        $seeder->run();
        
        $this->info('Achievement types seeded successfully.');
        
        return Command::SUCCESS;
    }
} 