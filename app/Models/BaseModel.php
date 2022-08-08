<?php

namespace App\Models;

/**
 * App\Models\BaseModel
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel query()
 * @mixin \Eloquent
 */
class BaseModel extends \Illuminate\Database\Eloquent\Model
{
    /**
     * @param array $attributes
     * @param array $options
     * @return static
     */
    public function updateModel(array $attributes = [], array $options = []): static
    {
        return tap($this)->update($attributes, $options);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param array $options
     * @return $this
     */
    public function updateModelValue(string $key, mixed $value, array $options = []): static
    {
        return $this->updateModel([$key => $value], $options);
    }
}