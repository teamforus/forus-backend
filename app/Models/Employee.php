<?php

namespace App\Models;

use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Employee
 *
 * @property int $id
 * @property string $identity_address
 * @property int $organization_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Employee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Employee withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Employee withoutTrashed()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 */
class Employee extends Model
{
    use SoftDeletes, HasLogs;

    const EVENT_CREATED = 'created';
    const EVENT_UPDATED = 'updated';
    const EVENT_DELETED = 'deleted';

    protected $fillable = [
        'identity_address', 'organization_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles() {
        return $this->belongsToMany(
            Role::class,
            (new EmployeeRole)->getTable()
        );
    }

    /**
     * @param string $role
     * @return bool
     */
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
