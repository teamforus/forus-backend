<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Permission
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property-read Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Permission whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Permission whereName($value)
 * @mixin \Eloquent
 */
class Permission extends BaseModel
{
    protected static Collection|null $memCache = null;

    protected $fillable = [
        'key', 'name'
    ];

    public $timestamps = false;

    /**
     * @return Collection
     */
    public static function allMemCached(): Collection
    {
        return self::$memCache ?: self::$memCache = self::all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany {
        return $this->belongsToMany(Role::class, (new RolePermission)->getTable());
    }
}
