<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SystemConfig
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemConfig whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemConfig whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemConfig whereValue($value)
 * @mixin \Eloquent
 */
class SystemConfig extends Model
{
    /**
     * @var string[]
     */
    protected $hidden = [
        'key',
        'value',
    ];
}
