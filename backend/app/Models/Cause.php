<?php

namespace App\Models;

use App\Transformers\CauseTransformer;
use Carbon\Carbon;


/**
 * @property Carbon created_at
 */
class Cause extends BaseModel
{
    public $transformer = CauseTransformer::class;


    /**
     * Relationships
     */

    // A cause can have many donations
    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    // Optionally, if you want to enforce using a specific Carbon instance
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value); // Returns Carbon instance
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        dd($value);
        return $this->where('id', $value)->firstOrFail();
    }
}
