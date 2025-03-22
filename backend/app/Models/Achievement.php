<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'achievement_type_id',
        'name',
        'description',
        'icon',
        'threshold',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'threshold' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the achievement type that owns the achievement.
     */
    public function achievementType(): BelongsTo
    {
        return $this->belongsTo(AchievementType::class);
    }

    /**
     * Get the users for the achievement.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('awarded_at')
            ->withTimestamps();
    }
} 