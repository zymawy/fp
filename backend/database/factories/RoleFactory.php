<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_name' =>$role =  $this->faker->randomElement(['Admin', 'Donor']),
            'privileges' => json_encode(
                $role === 'Admin' ?
                    ['manage_causes', 'view_reports']
                : ['donate', 'view_causes']),
        ];
    }
}
