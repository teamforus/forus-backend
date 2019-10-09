<?php

namespace App\Models;

/**
 * App\Models\Validator
 *
 * @property int $id
 * @property int $organization_id
 * @property string $identity_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Validator newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Validator newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Validator query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Validator whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Validator whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Validator whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Validator whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Validator whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Validator extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'key', 'name', 'organization_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }
}
