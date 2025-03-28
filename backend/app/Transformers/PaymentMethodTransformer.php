<?php

namespace App\Transformers;

use App\Models\PaymentMethod;
use PHPOpenSourceSaver\Fractal\TransformerAbstract as Transformer;

class PaymentMethodTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @param  \App\Models\PaymentMethod $paymentMethod
     * @return array
     */
    public function transform(PaymentMethod $paymentMethod)
    {
        return [
            'id' => $paymentMethod->id,
            'payment_method_id' => $paymentMethod->payment_method_id,
            'name' => $paymentMethod->name,
            'short_name' => $paymentMethod->short_name,
            'image_url' => $paymentMethod->image_url,
            'is_active' => (bool) $paymentMethod->is_active,
            'is_default' => (bool) $paymentMethod->is_default,
            'currency' => $paymentMethod->currency,
            'service_charge' => (float) $paymentMethod->service_charge,
            'total_amount' => (float) $paymentMethod->total_amount,
            'provider' => $paymentMethod->provider,
            'display_order' => $paymentMethod->display_order,
            'created_at' => $paymentMethod->created_at ? $paymentMethod->created_at->toIso8601String() : null,
            'updated_at' => $paymentMethod->updated_at ? $paymentMethod->updated_at->toIso8601String() : null,
        ];
    }
}
