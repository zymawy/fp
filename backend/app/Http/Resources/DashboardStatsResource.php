<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
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
            'data' => [
                'attributes' => [
                    'total_donations_amount' => (float)$this['total_donations_amount'],
                    'total_users_count' => (int)$this['total_users_count'],
                    'total_causes_count' => (int)$this['total_causes_count'],
                    'donations_growth' => (float)$this['donations_growth'],
                    'users_growth' => (float)$this['users_growth'],
                    'causes_growth' => (float)$this['causes_growth'],
                ]
            ]
        ];
    }
} 