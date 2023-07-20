<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\OrganizationContact
 *
 * @property int $id
 * @property int $organization_id
 * @property string $type
 * @property string $contact_key
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact whereContactKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationContact whereValue($value)
 * @mixin \Eloquent
 */
class OrganizationContact extends Model
{
    const TYPE_EMAIL = 'email';

    const FUND_BALANCE_LOW_EMAIL_KEY = 'fund_balance_low';
    const BANK_CONNECTION_EXPIRING_KEY = 'bank_connections_expiring';
    const PROVIDER_APPLIED_KEY = 'provider_applied';

    const TYPES = [
        self::TYPE_EMAIL
    ];

    public static array $availableContacts = [
        self::FUND_BALANCE_LOW_EMAIL_KEY => self::TYPE_EMAIL,
        self::BANK_CONNECTION_EXPIRING_KEY => self::TYPE_EMAIL,
        self::PROVIDER_APPLIED_KEY => self::TYPE_EMAIL,
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'organization_id', 'type', 'contact_key', 'value',
    ];

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}