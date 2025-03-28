<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardTrendsResource extends JsonResource
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
                    'weekly' => $this['weekly'],
                    'monthly' => $this['monthly']
                ]
            ]
        ];
    }
} 