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
    public const string VIEW_IMPLEMENTATIONS = 'view_implementations';
    public const string MANAGE_IMPLEMENTATION = 'manage_implementation';
    public const string MANAGE_IMPLEMENTATION_CMS = 'manage_implementation_cms';

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

    // offices
    public const string MANAGE_OFFICES = 'manage_offices';

    // finances
    public const string VIEW_FINANCES = 'view_finances';

    // providers
    public const string MANAGE_PROVIDERS = 'manage_providers';
    public const string MANAGE_PROVIDER_FUNDS = 'manage_provider_funds';

    // products
    public const string MANAGE_PRODUCTS = 'manage_products';

    // employees
    public const string MANAGE_EMPLOYEES = 'manage_employees';

    // bank connections
    public const string MANAGE_BANK_CONNECTIONS = 'manage_bank_connections';

    // reimbursements
    public const string MANAGE_REIMBURSEMENTS = 'manage_reimbursements';

    // payments
    public const string MANAGE_PAYMENT_METHODS = 'manage_payment_methods';
    public const string MAKE_DIRECT_PAYMENTS = 'make_direct_payments';
    public const string MANAGE_TRANSACTION_BULKS = 'manage_transaction_bulks';
    public const string VIEW_FUNDS_EXTRA_PAYMENTS = 'view_funds_extra_payments';

    // bi connection
    public const string MANAGE_BI_CONNECTION = 'manage_bi_connection';

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
