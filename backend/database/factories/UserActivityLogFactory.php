<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserActivityLog>
 */
class UserActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::inRandomOrder()->first()->id,
            'action' => $this->faker->randomElement(['logged_in', 'donated', 'updated_profile']),
            'timestamp' => $this->faker->dateTimeThisMonth,
        ];
    }
}
