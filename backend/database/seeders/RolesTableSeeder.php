<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id' => Str::uuid()->toString(),
                'role_name' => 'Admin',
                'privileges' => json_encode([
                    'manage_users',
                    'manage_categories',
                    'manage_causes',
                    'manage_donations',
                    'view_reports',
                    'manage_settings'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'role_name' => 'Manager',
                'privileges' => json_encode([
                    'manage_causes',
                    'manage_donations',
                    'view_reports'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'role_name' => 'Donor',
                'privileges' => json_encode([
                    'donate',
                    'view_causes',
                    'view_own_donations'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'role_name' => 'Guest',
                'privileges' => json_encode([
                    'view_causes'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('roles')->insert($roles);
    }
}
