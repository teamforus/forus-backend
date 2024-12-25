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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @mixin \Eloquent
 */
class Permission extends BaseModel
{
    protected static Collection|null $memCache = null;

    const string VALIDATE_RECORDS = 'validate_records';
    const string MANAGE_VALIDATORS = 'manage_validators';

    const string MANAGE_FUNDS = 'manage_funds';
    const string MANAGE_FUND_TEXTS = 'manage_fund_texts';

    const string MANAGE_PAYOUTS = 'manage_payouts';

    const string MANAGE_IMPLEMENTATION_NOTIFICATIONS = 'manage_implementation_notifications';

    const string MANAGE_IDENTITIES = 'manage_identities';
    const string VIEW_IDENTITIES = 'view_identities';

    protected $fillable = [
        'key', 'name',
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
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, (new RolePermission)->getTable());
    }
}
