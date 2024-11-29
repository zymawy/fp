<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cause>
 */
class CauseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'category' => $this->faker->word,
            'target_amount' => $this->faker->numberBetween(1000, 10000),
            'collected_amount' => $this->faker->numberBetween(0, 5000),
            'media_url' => $this->faker->imageUrl(),
        ];
    }
}
