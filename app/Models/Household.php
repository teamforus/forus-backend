<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Household.
 *
 * @property int $id
 * @property string $uid
 * @property int $organization_id
 * @property string $living_arrangement
 * @property int|null $count_people
 * @property int|null $count_minors
 * @property int|null $count_adults
 * @property string|null $city
 * @property string|null $street
 * @property string|null $house_nr
 * @property string|null $house_nr_addition
 * @property string|null $postal_code
 * @property string|null $neighborhood_name
 * @property string|null $municipality_name
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\HouseholdProfile[] $household_profiles
 * @property-read int|null $household_profiles_count
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Identity[] $profiles
 * @property-read int|null $profiles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereCountAdults($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereCountMinors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereCountPeople($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereHouseNr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereHouseNrAddition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereLivingArrangement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereMunicipalityName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereNeighborhoodName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household withoutTrashed()
 * @mixin \Eloquent
 */
class Household extends Model
{
    use SoftDeletes;

    public const string LIVING_ARRANGEMENT_UNKNOWN = 'unknown';
    public const string LIVING_ARRANGEMENT_SINGLE = 'single';
    public const string LIVING_ARRANGEMENT_SINGLE_PARENT_HOUSEHOLD = 'single_parent_household';
    public const string LIVING_ARRANGEMENT_COHABITING_WITH_PARTNER_WITH_AGREEMENT = 'cohabiting_with_partner_with_agreement';
    public const string LIVING_ARRANGEMENT_COHABITING_WITH_PARTNER_WITHOUT_AGREEMENT = 'cohabiting_with_partner_without_agreement';
    public const string LIVING_ARRANGEMENT_COHABITING_WITH_INCOME_DEPENDENT_CHILDREN = 'cohabiting_with_income_dependent_children';
    public const string LIVING_ARRANGEMENT_COHABITING_WITH_OTHER_SINGLES = 'cohabiting_with_other_singles';
    public const string LIVING_ARRANGEMENT_COHABITING_WITH_SPOUSE_OR_REGISTERED_PARTNER = 'cohabiting_with_spouse_or_registered_partner';
    public const string LIVING_ARRANGEMENT_MARRIED_OR_UNMARRIED_COHABITING = 'married_or_unmarried_cohabiting';
    public const string LIVING_ARRANGEMENT_OTHER = 'other';
    public const string LIVING_ARRANGEMENT_NOT_SPECIFIED = 'not_specified';

    public const array LIVING_ARRANGEMENTS = [
        self::LIVING_ARRANGEMENT_UNKNOWN,
        self::LIVING_ARRANGEMENT_SINGLE,
        self::LIVING_ARRANGEMENT_SINGLE_PARENT_HOUSEHOLD,
        self::LIVING_ARRANGEMENT_COHABITING_WITH_PARTNER_WITH_AGREEMENT,
        self::LIVING_ARRANGEMENT_COHABITING_WITH_PARTNER_WITHOUT_AGREEMENT,
        self::LIVING_ARRANGEMENT_COHABITING_WITH_INCOME_DEPENDENT_CHILDREN,
        self::LIVING_ARRANGEMENT_COHABITING_WITH_OTHER_SINGLES,
        self::LIVING_ARRANGEMENT_COHABITING_WITH_SPOUSE_OR_REGISTERED_PARTNER,
        self::LIVING_ARRANGEMENT_MARRIED_OR_UNMARRIED_COHABITING,
        self::LIVING_ARRANGEMENT_OTHER,
        self::LIVING_ARRANGEMENT_NOT_SPECIFIED,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'uid',
        'count_people',
        'count_minors',
        'count_adults',
        'organization_id',
        'living_arrangement',
        'city',
        'street',
        'house_nr',
        'house_nr_addition',
        'postal_code',
        'neighborhood_name',
        'municipality_name',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'living_arrangement' => 'string',
    ];

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany
     */
    public function household_profiles(): HasMany
    {
        return $this->hasMany(HouseholdProfile::class);
    }

    /**
     * @return BelongsToMany
     */
    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(
            Identity::class,
            HouseholdProfile::class,
            'household_id',
            'identity_id',
        );
    }
}
