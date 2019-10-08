<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Permission
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Permission whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Permission whereName($value)
 * @mixin \Eloquent
 */
class Permission extends Model
{
    protected static $memCache = null;
    protected $fillable = [
        'key', 'name'
    ];

    public $timestamps = false;

    /**
     * @return Permission[]|\Illuminate\Database\Eloquent\Collection|null
     */
    public static function allMemCached() {
        return self::$memCache ? self::$memCache : self::all();
    }
}
