<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
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
            'type' => 'partner',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'logo' => $this->logo,
                'description' => $this->description,
                'website' => $this->website,
                'is_featured' => $this->is_featured,
                'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
                'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
                'deleted_at' => $this->deleted_at ? $this->deleted_at->toIso8601String() : null,
            ],
            'relationships' => [
                'causes' => [
                    'data' => $this->when($this->relationLoaded('causes'), function () {
                        return $this->causes->map(function ($cause) {
                            return [
                                'type' => 'cause',
                                'id' => $cause->id
                            ];
                        });
                    }),
                ],
            ],
        ];
    }
} 