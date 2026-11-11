<?php

namespace App\Modules\VoucherPass\Models;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Traits\HasDbTokens;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Random\RandomException;

/**
 * @property int $id
 * @property int $organization_id
 * @property string|null $external_id
 * @property int $owner_identity_id
 * @property int $target_identity_id
 * @property string $code
 * @property string $print_label
 * @property string $state
 * @property bool $print_requested
 * @property int|null $employee_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Identity $owner_identity
 * @property-read Identity $target_identity
 * @property-read Employee|null $employee
 * @property-read Collection<int, VoucherPassType> $types
 * @property-read Collection<int, Fund> $explicit_funds
 */
class VoucherPass extends Model
{
    use HasDbTokens;

    public const string STATE_ACTIVE = 'active';
    public const string STATE_DEACTIVATED = 'deactivated';

    protected $fillable = [
        'organization_id', 'external_id', 'owner_identity_id', 'target_identity_id', 'code',
        'print_label', 'state', 'print_requested', 'employee_id',
    ];

    protected $casts = ['print_requested' => 'boolean'];

    protected $perPage = 10;

    /**
     * @throws RandomException
     * @return string
     */
    public static function generateCode(): string
    {
        return strtoupper(static::makeUniqueTokenCallback(function (string $value): bool {
            return static::query()->where('code', strtoupper($value))->doesntExist();
        }, 4, 3));
    }

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo
     */
    public function owner_identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'owner_identity_id');
    }

    /**
     * @return BelongsTo
     */
    public function target_identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'target_identity_id');
    }

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsToMany
     */
    public function types(): BelongsToMany
    {
        return $this->belongsToMany(
            VoucherPassType::class,
            'voucher_pass_type_links',
            'voucher_pass_id',
            'voucher_pass_type_id',
        );
    }

    /**
     * @return BelongsToMany
     */
    public function explicit_funds(): BelongsToMany
    {
        return $this->belongsToMany(Fund::class, 'voucher_pass_funds', 'voucher_pass_id', 'fund_id');
    }

    /**
     * @param string|null $value
     * @return void
     */
    public function setExternalIdAttribute(?string $value): void
    {
        $value = trim($value ?? '');
        $this->attributes['external_id'] = $value !== '' ? $value : null;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * @param string $value
     * @return void
     */
    public function setPrintLabelAttribute(string $value): void
    {
        $this->attributes['print_label'] = trim($value);
    }
}
