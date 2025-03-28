<?php

namespace App\Transformers;

use App\Models\Transaction;
use PHPOpenSourceSaver\Fractal\TransformerAbstract as Transformer;

class PaymentTransformer extends \PHPOpenSourceSaver\Fractal\TransformerAbstract
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [
        'donation',
    ];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [
        'donation' => DonationTransformer::class,
    ];

    /**
     * Transform the payment response.
     *
     * @param  \App\Models\Transaction|array $payment
     * @return array
     */
    public function transform($payment)
    {
        // If we're transforming a transaction model
        if ($payment instanceof Transaction) {
            return [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'payment_provider' => $payment->payment_provider,
                'payment_method' => $payment->payment_method,
                'amount' => (float) $payment->donation->amount,
                'currency_code' => $payment->donation->currency_code,
                'status' => $payment->payment_status,
                'created_at' => $payment->created_at->toIso8601String(),
                'updated_at' => $payment->updated_at->toIso8601String(),
                'donation' => $payment->donation,
                'recipient_name' => $payment->donation->user->name,
            ];
        }

        // If we're transforming an array
        return $payment;
    }
}
