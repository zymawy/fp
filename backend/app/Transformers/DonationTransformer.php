<?php

namespace App\Transformers;

use App\Models\Donation;
use PHPOpenSourceSaver\Fractal\TransformerAbstract;

class DonationTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array<string>
     */
    protected array $defaultIncludes = ['user', 'cause'];

    /**
     * List of resources possible to include
     *
     * @var array<string>
     */
    protected array $availableIncludes = ['transaction'];

    /**
     * Transform the donation model
     *
     * @param Donation $donation
     * @return array
     */
    public function transform(Donation $donation): array
    {
        return [
            'id' => $donation->id,
            'user_id' => $donation->user_id,
            'cause_id' => $donation->cause_id,
            'amount' => (float) $donation->amount,
            'total_amount' => (float) $donation->total_amount,
            'processing_fee' => (float) $donation->processing_fee,
            'is_anonymous' => (bool) $donation->is_anonymous,
            'cover_fees' => (bool) $donation->cover_fees,
            'currency_code' => $donation->currency_code,
            'payment_status' => $donation->payment_status,
            'payment_method_id' => $donation->payment_method_id,
            'payment_id' => $donation->payment_id,
            'is_gift' => (bool) $donation->is_gift,
            'gift_message' => $donation->gift_message,
            'recipient_name' => $donation->recipient_name,
            'recipient_email' => $donation->recipient_email,
            'created_at' => $donation->created_at?->toIso8601String(),
            'updated_at' => $donation->updated_at?->toIso8601String(),
        ];
    }
    
    /**
     * Include User
     *
     * @param Donation $donation
     * @return \PHPOpenSourceSaver\Fractal\Resource\Item|null
     */
    public function includeUser(Donation $donation)
    {
        if ($donation->user) {
            return $this->item($donation->user, new UserTransformer());
        }
        
        return null;
    }
    
    /**
     * Include Cause
     *
     * @param Donation $donation
     * @return \PHPOpenSourceSaver\Fractal\Resource\Item|null
     */
    public function includeCause(Donation $donation)
    {
        if ($donation->cause) {
            return $this->item($donation->cause, new CauseTransformer());
        }
        
        return null;
    }
    
    /**
     * Include Transaction
     *
     * @param Donation $donation
     * @return \PHPOpenSourceSaver\Fractal\Resource\Item|null
     */
    public function includeTransaction(Donation $donation)
    {
        if ($donation->transaction) {
            return $this->item($donation->transaction, new TransactionTransformer());
        }
        
        return null;
    }
}
