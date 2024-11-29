<?php

namespace App\Transformers;

use App\Models\Cause;
use Flugg\Responder\Transformers\Transformer;

class CauseTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [
        'donations' => DonationTransformer::class,
    ];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @param  \App\Models\Cause $cause
     * @return array
     */
    public function transform(Cause $cause)
    {
        return [
            'id' => (int) $cause->id,
            'title' => $cause->title,
            'description' => $cause->description,
            'category' => $cause->category,
            'target_amount' => $cause->target_amount,
            'collected_amount' => $cause->collected_amount,
            'media_url' => $cause->media_url,
            'created_at' => $cause->created_at
        ];
    }

}
