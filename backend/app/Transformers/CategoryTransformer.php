<?php

namespace App\Transformers;

use App\Models\Category;
use PHPOpenSourceSaver\Fractal\TransformerAbstract;

class CategoryTransformer extends TransformerAbstract
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
     * Transform a category model
     *
     * @param Category $category
     * @return array
     */
    public function transform(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon,
            'is_active' => (bool) $category->is_active,
            'causes_count' => $category->when($category->relationLoaded('causes'), function () use ($category) {
                return $category->causes->count();
            }, function () use ($category) {
                return $category->causes_count ?? 0;
            }),
            'created_at' => $category->created_at?->toIso8601String(),
            'updated_at' => $category->updated_at?->toIso8601String(),
        ];
    }
    
    /**
     * Include Causes
     *
     * @param Category $category
     * @return \PHPOpenSourceSaver\Fractal\Resource\Collection|null
     */
    public function includeCauses(Category $category)
    {
        if ($category->relationLoaded('causes')) {
            return $this->collection($category->causes, new CauseTransformer());
        }
        
        return null;
    }
} 