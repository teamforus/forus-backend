<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 * @property mixed $id
 * @property string $key
 * @property string $name
 * @property Collection $permissions
 * @package App
 */
class Role extends Model
{
    protected $fillable = [
        'key', 'name'
    ];

    public $timestamps = false;

    public function permissions() {
        return $this->belongsToMany(Permission::class, RolePermission::getModel()->getTable());
    }
}
