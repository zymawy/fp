<?php

namespace App\Models;

use App\Casts\Json;
use App\Transformers\RoleTransformer;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends BaseModel
{
    use SoftDeletes;
    
    public $transformer  = RoleTransformer::class;

    /**
     * Relationships
     */

    // A role can have many users
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function isAdmin()
    {
        return $this->role_name === 'Admin';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'privileges' => Json::class,
            'deleted_at' => 'datetime',
        ];
    }
}
