<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Employee
 * @property mixed $id
 * @property string $identity_address
 * @property mixed $role_id
 * @property mixed $organization_id
 * @property Organization $organization
 * @property Collection $roles
 * @package App\Models
 */
class Employee extends Model
{
    protected $fillable = [
        'identity_address', 'organization_id'
    ];

    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    public function roles() {
        return $this->belongsToMany(Role::class, EmployeeRole::getModel()->getTable());
    }
}
