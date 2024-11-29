<?php

namespace App\Models;

use App\Casts\Json;
use App\Transformers\RoleTransformer;

class Role extends BaseModel
{
    public $transformer  = RoleTransformer::class;

    /**
     * Relationships
     */

    // A role has many users
    public function users()
    {
        return $this->hasMany(User::class);
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
        ];
    }
}
