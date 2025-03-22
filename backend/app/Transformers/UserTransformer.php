<?php

namespace App\Transformers;

use App\Models\User;
use PHPOpenSourceSaver\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array<string>
     */
    protected array $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array<string>
     */
    protected array $availableIncludes = ['donations', 'activities'];
    
    /**
     * Transform a user model
     *
     * @param User $user
     * @return array
     */
    public function transform(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            // Excluding password and other sensitive fields
        ];
    }
    
    /**
     * Include Donations
     *
     * @param User $user
     * @return \PHPOpenSourceSaver\Fractal\Resource\Collection|null
     */
    public function includeDonations(User $user)
    {
        if ($user->relationLoaded('donations')) {
            return $this->collection($user->donations, new DonationTransformer());
        }
        
        return null;
    }
    
    /**
     * Include Activities
     *
     * @param User $user
     * @return \PHPOpenSourceSaver\Fractal\Resource\Collection|null
     */
    public function includeActivities(User $user)
    {
        if ($user->relationLoaded('activities')) {
            return $this->collection($user->activities, new UserActivityLogTransformer());
        }
        
        return null;
    }
}
