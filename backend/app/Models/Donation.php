<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Transformers\DonationTransformer;

class Donation extends Model
{
    use HasFactory, HasUuids;
    
    public $transformer  = DonationTransformer::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'cause_id',
        'amount',
        'is_anonymous',
        'total_amount',
        'cover_fees',
        'currency_code',
        'gift_message',
        'is_gift',
        'payment_method_id',
        'payment_status',
        'processing_fee',
        'recipient_email',
        'recipient_name',
        'payment_id',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'is_anonymous' => 'boolean',
        'cover_fees' => 'boolean',
        'is_gift' => 'boolean',
    ];
    
    /**
     * Get the user that owns the donation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the cause that owns the donation.
     */
    public function cause(): BelongsTo
    {
        return $this->belongsTo(Cause::class);
    }

    /**
     * Get the transaction for the donation.
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
