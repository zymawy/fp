<?php

namespace App\Models;

use App\Transformers\DonationTransformer;

class Donation extends BaseModel
{
    public $transformer  = DonationTransformer::class;


    /**
     * Relationships
     */

    // A donation belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A donation belongs to a cause
    public function cause()
    {
        return $this->belongsTo(Cause::class);
    }

    // A donation has one transaction
    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
}
