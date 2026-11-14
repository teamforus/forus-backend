<?php

namespace App\Modules\VoucherPass\Models;

use App\Models\Fund;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Collection<int, Fund> $funds
 * @property-read Collection<int, VoucherPass> $voucher_passes
 */
class VoucherPassType extends Model
{
    protected $fillable = ['organization_id', 'name'];

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsToMany
     */
    public function funds(): BelongsToMany
    {
        return $this->belongsToMany(Fund::class, 'voucher_pass_type_funds', 'voucher_pass_type_id', 'fund_id');
    }

    /**
     * @return BelongsToMany
     */
    public function voucher_passes(): BelongsToMany
    {
        return $this->belongsToMany(
            VoucherPass::class,
            'voucher_pass_type_links',
            'voucher_pass_type_id',
            'voucher_pass_id',
        );
    }

    /**
     * @param string $value
     * @return void
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = trim($value);
    }
}
