<?php

namespace App\Models;

use App\Transformers\TransactionTransformer;
use Illuminate\Database\Eloquent\Model;

class Transaction extends BaseModel
{
    public $transformer  = TransactionTransformer::class;


    /**
     * Relationships
     */

    // A transaction belongs to a donation
    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }
}
