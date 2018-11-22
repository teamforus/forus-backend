<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RolePermission
 * @property mixed $id
 * @property mixed $role_id
 * @property mixed $permission_id
 * @property Role $role
 * @property Permission $permission
 * @package App\Models
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
