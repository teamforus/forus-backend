<?php


namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait EloquentModel
 * @method static static find($id, $columns = ['*'])
 * @method static static make($attributes = array())
 * @method static static create($attributes = array())
 * @method static static findOrFail($id, $columns = array())
 * @method static static findOrNew($id, $columns = array())
 * @method static static firstOrNew($id, $columns = array())
 * @method static static firstOrCreate($columns = array())
 * @method static Collection pluck($column, $key = null)
 * @method static Builder whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder whereKey($id)
 * @package App\Models\Traits
 */
trait EloquentModel
{

}