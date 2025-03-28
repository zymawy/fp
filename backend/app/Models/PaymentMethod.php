<?php

namespace App\Models;

use App\Transformers\PaymentMethodTransformer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory, HasUuids;
    
    public $transformer = PaymentMethodTransformer::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_method_id',
        'name',
        'short_name',
        'image_url',
        'is_active',
        'is_default',
        'currency',
        'service_charge',
        'total_amount',
        'metadata',
        'provider',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'service_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'display_order' => 'integer',
    ];

    /**
     * Get the transactions associated with this payment method.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'payment_method', 'payment_method_id');
    }
}
