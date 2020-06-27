<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;

/**
 * App\Models\Role
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RolePermission[] $role_permissions
 * @property-read int|null $role_permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RoleTranslation[] $translations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereName($value)
 * @mixin \Eloquent
 */
class Role extends Model
{
    use Translatable;

    protected $fillable = [
        'key', 'name'
    ];

    public $timestamps = false;

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = [
        'description'
    ];

    public function employees() {
        return $this->belongsToMany(Employee::class, (
        new EmployeeRole
        )->getTable());
    }

    public function permissions() {
        return $this->belongsToMany(Permission::class, (
            new RolePermission
        )->getTable());
    }

    public function role_permissions() {
        return $this->hasMany(RolePermission::class);
    }
}
