<?php

namespace App\Models;

/**
 * App\Models\Model
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Model query()
 * @mixin \Eloquent
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    /**
     * @param array $attributes
     * @param array $options
     * @return bool|$this
     */
    public function updateModel(array $attributes = [], array $options = [])
    {
        return tap($this)->update($attributes, $options);
    }
}