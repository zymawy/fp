<?php

namespace App\Transformers;

use App\Models\Cause;
use PHPOpenSourceSaver\Fractal\TransformerAbstract;

class CauseTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array<string>
     */
    protected array $defaultIncludes = ['category'];

    /**
     * List of resources possible to include
     *
     * @var array<string>
     */
    protected array $availableIncludes = ['partner', 'donations', 'updates'];

    /**
     * Transform a cause model
     *
     * @param Cause $cause
     * @return array
     */
    public function transform(Cause $cause): array
    {
        $data = [
            'id' => $cause->id,
            'title' => $cause->title,
            'slug' => $cause->slug,
            'description' => $cause->description,
            'image' => $cause->media_url,
            'goal_amount' => (float) $cause->goal_amount,
            'raised_amount' => (float) $cause->raised_amount,
            'start_date' => $cause->start_date?->toIso8601String(),
            'end_date' => $cause->end_date?->toIso8601String(),
            'status' => $cause->status,
            'category_id' => $cause->category_id,
            'category_name' => $cause->category_id ?  $cause->category->name : null,
            'partner_id' => $cause->partner_id,
            'is_featured' => (bool) $cause->is_featured,
            'is_active' => (bool) $cause->is_active,
            'created_at' => $cause->created_at?->toIso8601String(),
            'updated_at' => $cause->updated_at?->toIso8601String(),
        ];

        // Include donation summary if the relationship is loaded
        if ($cause->relationLoaded('donations')) {
            $data['donations_count'] = $cause->donations->count();
            $data['donors_count'] = $cause->donations->pluck('user_id')->unique()->count();
            // Keep unique_donors for backward compatibility
            $data['unique_donors'] = $data['donors_count'];
        }

        // Include updates summary if the relationship is loaded
        if ($cause->relationLoaded('updates')) {
            $data['updates_count'] = $cause->updates->count();
            $data['latest_update'] = $cause->updates->sortByDesc('created_at')->first()?->created_at?->toIso8601String();
        }

        return $data;
    }

    /**
     * Include Category
     *
     * @param Cause $cause
     * @return \PHPOpenSourceSaver\Fractal\Resource\Item
     */
    public function includeCategory(Cause $cause)
    {
        if ($cause->category) {
            return $this->item($cause->category, function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            });
        }
    }

    /**
     * Include Partner
     *
     * @param Cause $cause
     * @return \PHPOpenSourceSaver\Fractal\Resource\Item
     */
    public function includePartner(Cause $cause)
    {
        if ($cause->partner) {
            return $this->item($cause->partner, function ($partner) {
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'logo' => $partner->logo,
                    'website' => $partner->website,
                    'description' => $partner->description,
                ];
            });
        }
    }

    /**
     * Include Donations
     *
     * @param Cause $cause
     * @return \PHPOpenSourceSaver\Fractal\Resource\Collection
     */
    public function includeDonations(Cause $cause)
    {
        if ($cause->donations) {
            return $this->collection($cause->donations, function ($donation) {
                return [
                    'id' => $donation->id,
                    'amount' => (float) $donation->amount,
                    'user_id' => $donation->user_id,
                    'status' => $donation->payment_status,
                    'created_at' => $donation->created_at?->toIso8601String(),
                    'anonymous' => (bool) $donation->is_anonymous,
                ];
            });
        }
    }

    /**
     * Include Updates
     *
     * @param Cause $cause
     * @return \PHPOpenSourceSaver\Fractal\Resource\Collection
     */
    public function includeUpdates(Cause $cause)
    {
        if ($cause->updates) {
            return $this->collection($cause->updates, function ($update) {
                return [
                    'id' => $update->id,
                    'title' => $update->title,
                    'content' => $update->content,
                    'created_at' => $update->created_at?->toIso8601String(),
                    'created_by' => $update->created_by,
                ];
            });
        }
    }
}
