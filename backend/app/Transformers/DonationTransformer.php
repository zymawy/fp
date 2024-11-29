<?php

namespace App\Transformers;

use App\Models\Donation;
use Flugg\Responder\Transformers\Transformer;

class DonationTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [
        'transaction' => TransactionTransformer::class,
    ];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [
        'user' => UserTransformer::class,
        'cause' => CauseTransformer::class,
    ];

    /**
     * Transform the model.
     *
     * @param  \App\Models\Donation $donation
     * @return array
     */
    public function transform(Donation $donation)
    {
        return [
            'id' => (int) $donation->id,
            'donation_amount' => $donation->donation_amount,
        ];
    }
}
