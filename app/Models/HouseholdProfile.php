<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\HouseholdProfile.
 *
 * @property int $id
 * @property int $household_id
 * @property int $profile_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Household $household
 * @property-read \App\Models\Profile $profile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProfile whereHouseholdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProfile whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProfile whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class HouseholdProfile extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'household_id',
        'profile_id',
    ];

    /**
     * @return BelongsTo
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * @return BelongsTo
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
