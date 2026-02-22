<?php

namespace Database\Factories;

use App\Models\Cause;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 10, 1000);
        $processingFee = round($amount * 0.029 + 0.30, 2);
        $coverFees = $this->faker->boolean(70);
        $totalAmount = $coverFees ? $amount + $processingFee : $amount;
        $isGift = $this->faker->boolean(20);

        return [
            'user_id' => User::factory(),
            'cause_id' => Cause::factory(),
            'amount' => $amount,
            'total_amount' => $totalAmount,
            'processing_fee' => $processingFee,
            'is_anonymous' => $this->faker->boolean(30),
            'cover_fees' => $coverFees,
            'currency_code' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'payment_status' => $this->faker->randomElement(['completed', 'pending', 'failed']),
            'payment_method_id' => $this->faker->bothify('pm_????_????????'),
            'payment_id' => $this->faker->bothify('pi_????_????????'),
            'is_gift' => $isGift,
            'gift_message' => $isGift ? $this->faker->sentence(10) : null,
            'recipient_name' => $isGift ? $this->faker->name() : null,
            'recipient_email' => $isGift ? $this->faker->email() : null,
        ];
    }

    /**
     * Define a state for completed donations.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'completed',
        ]);
    }

    /**
     * Define a state for pending donations.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Define a state for anonymous donations.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_anonymous' => true,
        ]);
    }
}
