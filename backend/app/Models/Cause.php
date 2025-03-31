<?php

namespace App\Models;

use App\Transformers\CauseTransformer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon created_at
 */
class Cause extends Model
{
    use HasFactory, HasUuids;

    public $transformer = CauseTransformer::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'goal_amount',
        'raised_amount',
        'status',
        'start_date',
        'end_date',
        'category_id',
        'partner_id',
        'featured_image',
        'is_featured',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'raised_amount' => 'decimal:2',
        'goal_amount' => 'decimal:2',
        'donor_count' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'featured' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Relationships
     */

    /**
     * Get the category that owns the cause.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the updates for the cause.
     */
    public function updates(): HasMany
    {
        return $this->hasMany(CauseUpdate::class);
    }

    /**
     * Alias for updates() to maintain backward compatibility
     */
    public function causeUpdates(): HasMany
    {
        return $this->updates();
    }

    /**
     * Get the donations for the cause.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get the partner associated with the cause.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    // Optionally, if you want to enforce using a specific Carbon instance
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value); // Returns Carbon instance
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
        return $this->where('id', $value)->firstOrFail();
    }
}
