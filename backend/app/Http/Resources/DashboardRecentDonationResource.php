<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardRecentDonationResource extends JsonResource
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
            'type' => 'donation',
            'attributes' => [
                'amount' => (float)$this->amount,
                'created_at' => $this->created_at->toIso8601String(),
                'currency_code' => $this->currency_code ?: 'USD',
            ],
            'relationships' => [
                'donor' => [
                    'data' => [
                        'id' => $this->user_id,
                        'type' => 'user',
                    ],
                ],
                'cause' => [
                    'data' => [
                        'id' => $this->cause_id,
                        'type' => 'cause',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
     */
    public function with($request)
    {
        $includedData = [];
        
        // Add users and causes to the included data
        foreach ($this->collection as $donation) {
            // Add user data if it exists
            if ($donation->user) {
                $includedData[] = [
                    'id' => $donation->user->id,
                    'type' => 'user',
                    'attributes' => [
                        'name' => $donation->user->name,
                    ],
                ];
            }
            
            // Add cause data if it exists
            if ($donation->cause) {
                $includedData[] = [
                    'id' => $donation->cause->id,
                    'type' => 'cause',
                    'attributes' => [
                        'title' => $donation->cause->title,
                    ],
                ];
            }
        }
        
        // Remove duplicates by converting to associative array with id-type as key
        $uniqueIncluded = [];
        foreach ($includedData as $item) {
            $key = $item['id'] . '-' . $item['type'];
            $uniqueIncluded[$key] = $item;
        }
        
        return [
            'included' => array_values($uniqueIncluded),
            'meta' => [
                'pagination' => [
                    'total' => $this->resource->total(),
                    'count' => $this->collection->count(),
                    'per_page' => $this->resource->perPage(),
                    'current_page' => $this->resource->currentPage(),
                    'total_pages' => $this->resource->lastPage(),
                ],
            ],
        ];
    }
} 