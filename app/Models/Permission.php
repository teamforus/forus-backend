<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Permission
 * @property mixed $id
 * @property string $key
 * @property string $name
 * @package App\Models
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
