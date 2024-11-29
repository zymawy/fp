<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancialReport>
 */
class FinancialReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period' => $this->faker->monthName . ' ' . $this->faker->year,
            'total_donations' => $this->faker->numberBetween(10000, 50000),
            'total_expenditure' => $this->faker->numberBetween(5000, 40000),
            'cause_id' => \App\Models\Cause::inRandomOrder()->first()->id,
        ];
    }
}
