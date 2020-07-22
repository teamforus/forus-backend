<?php

namespace App\Models;

/**
 * App\Models\Permission
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
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
        return self::$memCache ?: self::all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles() {
        return $this->belongsToMany(Role::class, (
        new RolePermission
        )->getTable());
    }
}
