<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Permission.
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
class Permission extends Model
{
    // validations
    public const string VALIDATE_RECORDS = 'validate_records';
    public const string MANAGE_VALIDATORS = 'manage_validators';

    // funds
    public const string VIEW_FUNDS = 'view_funds';
    public const string MANAGE_FUNDS = 'manage_funds';
    public const string MANAGE_FUND_TEXTS = 'manage_fund_texts';

    // payouts
    public const string MANAGE_PAYOUTS = 'manage_payouts';

    // implementations
    public const string MANAGE_IMPLEMENTATION_NOTIFICATIONS = 'manage_implementation_notifications';

    // identities
    public const string VIEW_IDENTITIES = 'view_identities';
    public const string MANAGE_IDENTITIES = 'manage_identities';

    // organizations
    public const string MANAGE_ORGANIZATION = 'manage_organization';

    // vouchers
    public const string SCAN_VOUCHERS = 'scan_vouchers';
    public const string VIEW_VOUCHERS = 'view_vouchers';
    public const string MANAGE_VOUCHERS = 'manage_vouchers';

    // profiles
    public const string VIEW_PERSON_BSN_DATA = 'view_person_bsn_data';

    public $timestamps = false;
    protected static Collection|null $memCache = null;

    protected $fillable = [
        'key', 'name',
    ];

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
        return $this->belongsToMany(Role::class, (new RolePermission())->getTable());
    }
}
