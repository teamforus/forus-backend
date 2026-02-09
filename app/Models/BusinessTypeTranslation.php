<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BusinessTypeTranslation.
 *
 * @property int $id
 * @property int $business_type_id
 * @property string $locale
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessTypeTranslation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessTypeTranslation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessTypeTranslation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessTypeTranslation whereBusinessTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessTypeTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessTypeTranslation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessTypeTranslation whereName($value)
 * @mixin \Eloquent
 */
class BusinessTypeTranslation extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];
}
