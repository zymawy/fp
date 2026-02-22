<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = $this->faker->randomElement(['Admin', 'Donor']);

        return [
            'role_name'  => $role,
            'privileges' => $role === 'Admin'
                ? ['manage_causes', 'view_reports']
                : ['donate', 'view_causes'],
        ];
    }
}
