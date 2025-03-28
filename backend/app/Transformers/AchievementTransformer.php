<?php

namespace App\Transformers;

use App\Models\Achievement;
use PHPOpenSourceSaver\Fractal\TransformerAbstract;

class AchievementTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array<string>
     */
    protected array $defaultIncludes = ['achievementType'];

    /**
     * Transform the achievement model
     *
     * @param Achievement $achievement
     * @return array
     */
    public function transform(Achievement $achievement): array
    {
        return [
            'id' => $achievement->id,
            'user_id' => $achievement->user_id,
            'achievement_type_id' => $achievement->achievement_type_id,
            'achieved_at' => $achievement->created_at->toIso8601String(),
            'created_at' => $achievement->created_at->toIso8601String(),
            'updated_at' => $achievement->updated_at->toIso8601String(),
        ];
    }

    /**
     * Include AchievementType
     *
     * @param Achievement $achievement
     * @return \PHPOpenSourceSaver\Fractal\Resource\Item
     */
    public function includeAchievementType(Achievement $achievement)
    {
        return $this->item($achievement->achievementType, new AchievementTypeTransformer);
    }
}
