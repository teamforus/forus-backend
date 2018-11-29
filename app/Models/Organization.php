<?php

namespace App\Models;

use App\Services\MediaService\Traits\HasMedia;
use App\Services\MediaService\Models\Media;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder;

/**
 * Class Organization
 * @property mixed $id
 * @property string $identity_address
 * @property string $name
 * @property string $iban
 * @property string $email
 * @property string $phone
 * @property string $kvk
 * @property string $btw
 * @property Media $logo
 * @property Collection $funds
 * @property Collection $vouchers
 * @property Collection $products
 * @property Collection $validators
 * @property Collection $supplied_funds
 * @property Collection $supplied_funds_approved
 * @property Collection $organization_funds
 * @property Collection $product_categories
 * @property Collection $voucher_transactions
 * @property Collection $funds_voucher_transactions
 * @property Collection $offices
 * @property Collection $employees
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Organization extends Model
{
    use HasMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'name', 'iban', 'email', 'phone', 'kvk', 'btw'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function funds() {
        return $this->hasMany(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products() {
        return $this->hasMany(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function offices() {
        return $this->hasMany(Office::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucher_transactions() {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function funds_voucher_transactions() {
        return $this->hasManyThrough(VoucherTransaction::class, Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function product_categories() {
        return $this->belongsToMany(
            ProductCategory::class,
            'organization_product_categories'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supplied_funds() {
        return $this->belongsToMany(
            Fund::class,
            'fund_providers'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supplied_funds_approved() {
        return $this->belongsToMany(
            Fund::class,
            'fund_providers'
        )->where('fund_providers.state', 'approved');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function organization_funds() {
        return $this->hasMany(FundProvider::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function validators() {
        return $this->hasMany(Validator::class);
    }

    /**
     * Get organization logo
     * @return MorphOne
     */
    public function logo() {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'organization_logo'
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function vouchers() {
        return $this->hasManyThrough(Voucher::class, Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees() {
        return $this->hasMany(Employee::class);
    }

    /**
     * Returns identity organization roles
     *
     * @param $identityAddress
     * @return Collection
     */
    public function identityRoles($identityAddress) {
        /** @var Employee $employee */
        $employee = $this->employees()->where('identity_address', $identityAddress)->first();

        return $employee ? $employee->roles : collect([]);
    }

    /**
     * Returns identity organization permissions
     * @param $identityAddress
     * @return \Illuminate\Support\Collection
     */
    public function identityPermissions($identityAddress) {
        if (strcmp($identityAddress, $this->identity_address) === 0) {
            return Permission::all();
        }

        $roles = $this->identityRoles($identityAddress);

        return $roles->pluck('permissions')->flatten()->unique('id');
    }

    /**
     * @param $identityAddress
     * @param $permissions
     * @param $all boolean
     * @return bool
     */
    public function identityCan($identityAddress, $permissions, $all = true) {
        // as owner of the organization you don't have any restrictions
        if (strcmp($identityAddress, $this->identity_address) === 0) {
            return true;
        }

        // retrieving the list of all the permissions that identity have
        $identityPermissionKeys = $this->identityPermissions(
            $identityAddress
        )->pluck('key');

        // convert string to array
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        if (!$all) {
            return $identityPermissionKeys->intersect($permissions)->count() > 0;
        }

        // check if all the requirements are satisfied
        return $identityPermissionKeys->intersect($permissions)->count() ==
            count($permissions);
    }

    /**
     * @param $identityAddress
     * @param $permissions
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function queryByIdentityPermissions (
        $identityAddress,
        $permissions = false
    ) {
        // convert string to array
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        /**
         * Query all the organizations where identity_address has permissions
         * or is the creator
         */
        return Organization::query()->whereIn('id', function(Builder $query) use ($identityAddress, $permissions) {
            $query->select(['organization_id'])->from(Employee::getModel()->getTable())->where([
                'identity_address' => $identityAddress
            ])->whereIn('id', function (Builder $query) use ($permissions) {
                $query->select('employee_id')->from(EmployeeRole::getModel()->getTable())->whereIn('role_id', function (Builder $query) use ($permissions) {
                    $query->select(['id'])->from(Role::getModel()->getTable())->whereIn('id', function (
                        Builder $query
                    )  use ($permissions) {
                        return $query->select(['role_id'])->from(RolePermission::getModel()->getTable())->whereIn('permission_id', function (Builder $query) use ($permissions) {
                            $query->select('id')->from(Permission::getModel()->getTable());

                            // allow any permission
                            if ($permissions !== false) {
                                $query->whereIn('key', $permissions);
                            }

                            return $query;
                        });
                    })->get();
                });
            });
        })->orWhere('identity_address', $identityAddress);
    }
}
