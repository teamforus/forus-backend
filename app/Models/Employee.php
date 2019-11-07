<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Employee
 *
 * @property int $id
 * @property string $identity_address
 * @property int $organization_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'identity_address', 'organization_id'
    ];

    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    public function roles() {
        return $this->belongsToMany(
            Role::class,
            (new EmployeeRole)->getTable()
        );
    }

    public function hasRole(string $role) {
        return $this->roles()->where('key', '=', $role)->count() > 0;
    }

    /**
     * @param $identity_address
     * @return bool|self|\Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public static function getEmployee($identity_address) {
        return self::where(compact('identity_address'))->first() ?? false;
    }
}
