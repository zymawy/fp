<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // First, seed the roles table
            RolesTableSeeder::class,
            
            // Then seed users (depends on roles)
            UsersTableSeeder::class,
            
            // Then seed categories and causes
            CategoriesTableSeeder::class,
            
            // Seed historical data across multiple years
            HistoricalDataSeeder::class,
            HistoricalTransactionsSeeder::class,
            HistoricalFinancialReportsSeeder::class,
            HistoricalCauseUpdatesSeeder::class,
            
            // Seed current data
            CausesTableSeeder::class,
            
            // Seed achievement types (before achievements)
            AchievementTypesTableSeeder::class,
            
            // Seed partners
            PartnersTableSeeder::class,
            
            // Then seed donations (depends on users and causes)
            DonationsTableSeeder::class,
            
            // Then seed transactions (depends on donations)
            TransactionsTableSeeder::class,
            
            // Seed cause updates (depends on causes)
            CauseUpdatesTableSeeder::class,
            
            // Seed achievements (depends on users and achievement types)
            AchievementsTableSeeder::class,
            
            // Then seed various reports and logs
            UserActivityLogsTableSeeder::class,
            CauseReportsTableSeeder::class,
            FinancialReportsTableSeeder::class,
        ]);
    }
}
