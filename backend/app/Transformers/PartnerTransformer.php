<?php

namespace App\Transformers;

use App\Models\Partner;
use PHPOpenSourceSaver\Fractal\TransformerAbstract;

class PartnerTransformer extends TransformerAbstract
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
    protected array $availableIncludes = ['causes'];

    /**
     * Transform the partner model
     *
     * @param Partner $partner
     * @return array
     */
    public function transform(Partner $partner): array
    {
        return [
            'id' => $partner->id,
            'name' => $partner->name,
            'logo' => $partner->logo,
            'description' => $partner->description,
            'website' => $partner->website,
            'is_featured' => (bool) $partner->is_featured,
            'created_at' => $partner->created_at?->toIso8601String(),
            'updated_at' => $partner->updated_at?->toIso8601String(),
            'deleted_at' => $partner->deleted_at?->toIso8601String(),
        ];
    }

    /**
     * Include Causes
     *
     * @param Partner $partner
     * @return \PHPOpenSourceSaver\Fractal\Resource\Collection|null
     */
    public function includeCauses(Partner $partner)
    {
        if (!$partner->relationLoaded('causes')) {
            return null;
        }
        
        return $this->collection($partner->causes, new CauseTransformer());
    }
} 