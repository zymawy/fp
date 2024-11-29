<?php

namespace App\Transformers;

use App\Models\UserActivityLog;
use Flugg\Responder\Transformers\Transformer;

class UserActivityLogTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @param  \App\Models\UserActivityLog $userActivityLog
     * @return array
     */
    public function transform(UserActivityLog $userActivityLog)
    {
        return [
            'id' => (int) $userActivityLog->id,
        ];
    }
}
