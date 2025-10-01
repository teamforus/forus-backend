<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ProfileRelation.
 *
 * @property int $id
 * @property int $profile_id
 * @property int $related_profile_id
 * @property string $type
 * @property string $subtype
 * @property bool $living_together
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $living_together_locale
 * @property-read string $subtype_locale
 * @property-read string $type_locale
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Models\Profile $profile
 * @property-read \App\Models\Profile $related_profile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation whereLivingTogether($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation whereRelatedProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation whereSubtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileRelation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProfileRelation extends Model
{
    public const string TYPE_PARTNER = 'partner';
    public const string TYPE_PARENT_CHILD = 'parent_child';
    public const string TYPE_HOUSEMATE = 'housemate';

    public const array TYPES = [
        self::TYPE_PARTNER,
        self::TYPE_PARENT_CHILD,
        self::TYPE_HOUSEMATE,
    ];

    /**
     * @var string[]
     */
    public const array SUBTYPES_PARTNER = [
        'partner_married',
        'partner_registered',
        'partner_unmarried',
        'partner_other',
    ];

    /**
     * @var string[]
     */
    public const array SUBTYPES_PARENT_CHILD = [
        'parent_child',
        'foster_parent_foster_child',
    ];

    /**
     * @var string[]
     */
    public const array SUBTYPES_HOUSEMATE = [
        'parent',
        'in_law',
        'grandparent_or_sibling',
        'room_renter',
        'room_landlord',
        'boarder_or_host',
        'other',
    ];

    /**
     * @var string[]
     */
    public const array SUBTYPES_BY_TYPE = [
        self::TYPE_PARTNER => self::SUBTYPES_PARTNER,
        self::TYPE_PARENT_CHILD => self::SUBTYPES_PARENT_CHILD,
        self::TYPE_HOUSEMATE => self::SUBTYPES_HOUSEMATE,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'profile_id',
        'organization_id',
        'related_profile_id',
        'type',
        'subtype',
        'living_together',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'type' => 'string',
        'subtype' => 'string',
        'living_together' => 'boolean',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function related_profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'related_profile_id');
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getTypeLocaleAttribute(): string
    {
        return [
            static::TYPE_PARTNER => __('profile.relation.type.partner'),
            static::TYPE_PARENT_CHILD => __('profile.relation.type.parent_child'),
            static::TYPE_HOUSEMATE => __('profile.relation.type.housemate'),
        ][$this->type] ?? $this->type;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getSubtypeLocaleAttribute(): string
    {
        if (!$this->subtype) {
            return 'Geen subtype';
        }

        return [
            static::TYPE_PARTNER => [
                'partner_married' => __('profile.relation.subtype.partner_married'),
                'partner_registered' => __('profile.relation.subtype.partner_registered'),
                'partner_unmarried' => __('profile.relation.subtype.partner_unmarried'),
                'partner_other' => __('profile.relation.subtype.partner_other'),
            ],
            static::TYPE_PARENT_CHILD => [
                'parent_child' => __('profile.relation.subtype.parent_child'),
                'foster_parent_foster_child' => __('profile.relation.subtype.foster_parent_foster_child'),
            ],
            static::TYPE_HOUSEMATE => [
                'parent' => __('profile.relation.subtype.parent'),
                'in_law' => __('profile.relation.subtype.in_law'),
                'grandparent_or_sibling' => __('profile.relation.subtype.grandparent_or_sibling'),
                'room_renter' => __('profile.relation.subtype.room_renter'),
                'room_landlord' => __('profile.relation.subtype.room_landlord'),
                'boarder_or_host' => __('profile.relation.subtype.boarder_or_host'),
                'other' => __('profile.relation.subtype.other'),
            ],
        ][$this->type][$this->subtype] ?? 'Invalid subtype';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getLivingTogetherLocaleAttribute(): string
    {
        return $this->living_together ?
            __('profile.relation.living_together.yes') :
            __('profile.relation.living_together.no');
    }
}
