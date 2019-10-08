<?php

namespace App\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * App\Models\RolePermission
 *
 * @property int $id
 * @property int $role_id
 * @property int $permission_id
 * @property-read \App\Models\Permission $permission
 * @property-read \App\Models\Role $role
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RolePermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RolePermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RolePermission query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RolePermission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RolePermission wherePermissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RolePermission whereRoleId($value)
 * @mixin \Eloquent
 */
class RolePermission extends Model
{
    protected $fillable = [
        'role_id', 'permission_id'
    ];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role() {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function permission() {
        return $this->belongsTo(Permission::class);
    }
}
