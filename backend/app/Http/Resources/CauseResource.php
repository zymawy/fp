<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CauseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'goal_amount' => $this->goal_amount,
            'current_amount' => $this->current_amount,
            'featured_image' => $this->featured_image,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'partner' => new PartnerResource($this->whenLoaded('partner')),
            'cause_updates' => CauseUpdateResource::collection($this->whenLoaded('causeUpdates')),
            'donations_count' => $this->when($this->relationLoaded('donations'), function () {
                return $this->donations->count();
            }),
            'donors_count' => $this->when($this->relationLoaded('donations'), function () {
                return $this->donations->unique('user_id')->count();
            }),
            'donor_count' => $this->when($this->relationLoaded('donations'), function () {
                return $this->donations->unique('user_id')->count();
            }),
            'percentage_funded' => $this->when($this->goal_amount > 0, function () {
                return round(($this->current_amount / $this->goal_amount) * 100, 2);
            }, 0),
        ];
    }
} 