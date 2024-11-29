<?php

namespace App\Models;

use Flugg\Responder\Contracts\Transformable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends \Illuminate\Database\Eloquent\Model  implements Transformable
{
    use HasFactory, SoftDeletes;
    public $transformer = null;

    public function transformer()
    {
        return $this->transformer;
    }
}