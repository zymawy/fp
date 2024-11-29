<?php

namespace App\Models;


use App\Transformers\UserActivityLogTransformer;

class UserActivityLog extends BaseModel
{
    public $transformer = UserActivityLogTransformer::class;

    /**
     * Relationships
     */

    // An activity log belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
