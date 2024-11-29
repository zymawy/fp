<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Role::insert([
            ['role_name' => 'Admin', 'privileges' => json_encode(['manage_causes', 'view_reports'])],
            ['role_name' => 'Donor', 'privileges' => json_encode(['donate', 'view_causes'])],
        ]);
    }
}
