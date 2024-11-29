<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the roles table first as it's referenced by other tables
        $this->call([
            RolesTableSeeder::class,
        ]);

        // Seed the users table
        $this->call([
            UsersTableSeeder::class,
        ]);

        // Seed causes
        $this->call([
            CausesTableSeeder::class,
        ]);

        // Seed donations
        $this->call([
            DonationsTableSeeder::class,
        ]);

        // Seed transactions
        $this->call([
            TransactionsTableSeeder::class,
        ]);

        // Optionally seed financial reports and activity logs
        $this->call([
            FinancialReportsTableSeeder::class,
            UserActivityLogsTableSeeder::class,
        ]);
    }
}
