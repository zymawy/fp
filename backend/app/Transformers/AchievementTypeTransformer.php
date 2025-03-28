<?php

namespace App\Transformers;

use App\Models\AchievementType;
use PHPOpenSourceSaver\Fractal\TransformerAbstract;

class AchievementTypeTransformer extends TransformerAbstract
{
    /**
     * Transform the achievement type model
     *
     * @param AchievementType $achievementType
     * @return array
     */
    public function transform(AchievementType $achievementType): array
    {
        return [
            'id' => $achievementType->id,
            'title' => $achievementType->title,
            'description' => $achievementType->description,
            'icon' => $achievementType->icon,
            'created_at' => $achievementType->created_at->toIso8601String(),
            'updated_at' => $achievementType->updated_at->toIso8601String(),
        ];
    }
}
