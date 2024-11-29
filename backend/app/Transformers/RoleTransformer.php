<?php

namespace App\Transformers;

use App\Models\Role;
use Flugg\Responder\Transformers\Transformer;

class RoleTransformer extends Transformer
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
     * @param  \App\Models\Role $role
     * @return array
     */
    public function transform(Role $role)
    {
        return [
            'id' => (int) $role->id,
        ];
    }
}
