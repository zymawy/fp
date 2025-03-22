<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CauseUpdate extends Model
{
    use HasFactory, HasUuids;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cause_id',
        'title',
        'content',
    ];
    
    /**
     * Get the cause that owns the update.
     */
    public function cause(): BelongsTo
    {
        return $this->belongsTo(Cause::class);
    }
} 