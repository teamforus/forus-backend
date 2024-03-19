<?php

namespace App\Models;

use Closure;

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
     */
    public function updateModel(array $attributes = [], array $options = []): bool
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

    /**
     * @param Closure $function
     *
     * @return $this
     */
    public function closure(Closure $function): static
    {
        $function($this);

        return $this;
    }
}