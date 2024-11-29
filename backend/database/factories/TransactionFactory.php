<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'donation_id' => \App\Models\Donation::inRandomOrder()->first()->id,
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'bank_transfer']),
            'payment_status' => $this->faker->randomElement(['completed', 'pending', 'failed']),
            'transaction_id' => $this->faker->uuid,
            'timestamp' => $this->faker->dateTimeThisYear,
        ];
    }
}
