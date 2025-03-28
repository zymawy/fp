<?php

namespace App\Transformers;

use App\Models\User;
use PHPOpenSourceSaver\Fractal\TransformerAbstract;

class ProfileTransformer extends TransformerAbstract
{
    /**
     * Transform the User model.
     *
     * @param User $user
     * @return array
     */
    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar_url' => $user->avatar_url,
            'phone_number' => $user->phone_number,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
} 