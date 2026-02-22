<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Cause;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cause>
 */
class CauseFactory extends Factory
{
    protected $model = Cause::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->randomNumber(5),
            'description' => $this->faker->paragraph(3),
            'goal_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'raised_amount' => 0,
            'status' => 'active',
            'category_id' => Category::factory(),
            'is_featured' => $this->faker->boolean(20),
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
        ];
    }

    /**
     * Mark the cause as featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Mark the cause as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Mark the cause as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
