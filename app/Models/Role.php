<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Role
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Permission[] $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RolePermission[] $role_permissions
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereName($value)
 * @mixin \Eloquent
 * @property-read int|null $permissions_count
 * @property-read int|null $role_permissions_count
 */
class Role extends Model
{
    protected $fillable = [
        'key', 'name'
    ];

    public $timestamps = false;

    public function permissions() {
        return $this->belongsToMany(Permission::class, (
            new RolePermission
        )->getTable());
    }

    public function role_permissions() {
        return $this->hasMany(RolePermission::class);
    }
}
