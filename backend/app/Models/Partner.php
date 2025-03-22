<?php

namespace App\Models;

use App\Transformers\PartnerTransformer;
use Flugg\Responder\Contracts\Transformable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model implements Transformable
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'logo',
        'description',
        'website',
        'is_featured',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the transformer for the model.
     *
     * @return string
     */
    public function transformer()
    {
        return \App\Transformers\PartnerTransformer::class;
    }

    /**
     * Get the causes for the partner.
     */
    public function causes(): HasMany
    {
        return $this->hasMany(Cause::class);
    }
} 