<?php

namespace Database\Factories;

use App\Models\Cause;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
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
        
        $paymentStatuses = ['completed', 'pending', 'failed', 'refunded'];
        $currencyCodes = ['USD', 'EUR', 'GBP'];
        
        // Safely get a random user ID
        try {
            $userId = Schema::hasTable('users') ? 
                User::inRandomOrder()->first()?->id : 
                $this->faker->uuid();
        } catch (\Exception $e) {
            $userId = $this->faker->uuid();
        }
        
        // Safely get a random cause ID
        try {
            $causeId = Schema::hasTable('causes') ? 
                Cause::inRandomOrder()->first()?->id : 
                $this->faker->uuid();
        } catch (\Exception $e) {
            $causeId = $this->faker->uuid();
        }
        
        return [
            'user_id' => $userId,
            'cause_id' => $causeId,
            'amount' => $amount,
            'total_amount' => $totalAmount,
            'processing_fee' => $processingFee,
            'is_anonymous' => $this->faker->boolean(30),
            'cover_fees' => $coverFees,
            'currency_code' => $this->faker->randomElement($currencyCodes),
            'payment_status' => $this->faker->randomElement($paymentStatuses),
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
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => 'completed',
            ];
        });
    }
}
