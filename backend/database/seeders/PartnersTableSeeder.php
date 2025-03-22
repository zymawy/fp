<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'World Food Programme',
                'logo' => 'https://source.unsplash.com/400x200/?food,charity,humanitarian',
                'website' => 'https://www.wfp.org',
                'order' => 1,
                'is_active' => true,
                'is_featured' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Doctors Without Borders',
                'logo' => 'https://source.unsplash.com/400x200/?medical,doctors,humanitarian',
                'website' => 'https://www.doctorswithoutborders.org',
                'order' => 2,
                'is_active' => true,
                'is_featured' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'UNICEF',
                'logo' => 'https://source.unsplash.com/400x200/?children,education,humanitarian',
                'website' => 'https://www.unicef.org',
                'order' => 3,
                'is_active' => true,
                'is_featured' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Red Cross',
                'logo' => 'https://source.unsplash.com/400x200/?medical,emergency,humanitarian',
                'website' => 'https://www.redcross.org',
                'order' => 4,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Save the Children',
                'logo' => 'https://source.unsplash.com/400x200/?children,education,charity',
                'website' => 'https://www.savethechildren.org',
                'order' => 5,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Oxfam',
                'logo' => 'https://source.unsplash.com/400x200/?poverty,hunger,humanitarian',
                'website' => 'https://www.oxfam.org',
                'order' => 6,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'World Wildlife Fund',
                'logo' => 'https://source.unsplash.com/400x200/?wildlife,conservation,nature',
                'website' => 'https://www.worldwildlife.org',
                'order' => 7,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Habitat for Humanity',
                'logo' => 'https://source.unsplash.com/400x200/?housing,construction,community',
                'website' => 'https://www.habitat.org',
                'order' => 8,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Care International',
                'logo' => 'https://source.unsplash.com/400x200/?women,poverty,humanitarian',
                'website' => 'https://www.care-international.org',
                'order' => 9,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Greenpeace',
                'logo' => 'https://source.unsplash.com/400x200/?environment,climate,activism',
                'website' => 'https://www.greenpeace.org',
                'order' => 10,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        DB::table('partners')->insert($partners);
        
        $this->command->info('Created ' . count($partners) . ' partners');
    }
} 