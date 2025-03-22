<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get role IDs
        $adminRoleId = DB::table('roles')->where('role_name', 'Admin')->value('id');
        $managerRoleId = DB::table('roles')->where('role_name', 'Manager')->value('id');
        $donorRoleId = DB::table('roles')->where('role_name', 'Donor')->value('id');
        
        // Create admin user
        $adminId = Str::uuid()->toString();
        DB::table('users')->insert([
            'id' => $adminId,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Link admin to role
        if ($adminRoleId) {
            DB::table('role_user')->insert([
                'id' => Str::uuid()->toString(),
                'user_id' => $adminId,
                'role_id' => $adminRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Create manager user
        $managerId = Str::uuid()->toString();
        DB::table('users')->insert([
            'id' => $managerId,
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Link manager to role
        if ($managerRoleId) {
            DB::table('role_user')->insert([
                'id' => Str::uuid()->toString(),
                'user_id' => $managerId,
                'role_id' => $managerRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Create regular users with donor role
        $userIds = [];
        for ($i = 1; $i <= 20; $i++) {
            $userId = Str::uuid()->toString();
            $userIds[] = $userId;
            
            DB::table('users')->insert([
                'id' => $userId,
                'name' => $faker->name,
                'email' => "user{$i}@example.com",
                'email_verified_at' => $faker->randomElement([now(), null]),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);
            
            // Link user to donor role (for some users)
            if ($donorRoleId && $faker->boolean(80)) {
                DB::table('role_user')->insert([
                    'id' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'role_id' => $donorRoleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Store user IDs for other seeders to use
        $this->command->info('User IDs stored for other seeders');
        app()->instance('seeded_user_ids', $userIds);
    }
}
