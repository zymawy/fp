<?php

namespace App\Transformers;

use App\Models\Transaction;
use PHPOpenSourceSaver\Fractal\TransformerAbstract as Transformer;

class TransactionTransformer extends Transformer
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
     * Transform the model.
     *
     * @param  \App\Models\Transaction $transaction
     * @return array
     */
    public function transform(Transaction $transaction)
    {
        return [
            'id' => $transaction->id,
            'transaction_id' => $transaction->transaction_id,
            'donation_id' => $transaction->donation_id,
            'payment_provider' => $transaction->payment_provider,
            'payment_method' => $transaction->payment_method,
            'amount' => (float) $transaction->amount,
            'currency_code' => $transaction->currency_code,
            'status' => $transaction->status,
            'payment_data' => $transaction->payment_data,
            'created_at' => $transaction->created_at ? $transaction->created_at->toIso8601String() : null,
            'updated_at' => $transaction->updated_at ? $transaction->updated_at->toIso8601String() : null,
        ];
    }
}
