<?php

namespace App\Transformers;

use App\Models\Transaction;
use Flugg\Responder\Transformers\Transformer;

class TransactionTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [

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
            'id' => (int) $transaction->id,
            'payment_method' => $transaction->payment_method,
            'payment_status' => $transaction->payment_status,
            'transaction_id' => $transaction->transaction_id,
            'timestamp' => $transaction->timestamp,
        ];
    }
}
